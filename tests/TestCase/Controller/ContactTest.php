<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Le formulaire de contact public, du point de vue d'un visiteur anonyme.
 *
 * C'est le seul formulaire du site ouvert à tous, donc le seul exposé aux
 * robots. Les tests le parcourent en résolvant réellement le captcha plutôt
 * qu'en le contournant : contourner prouverait que le chemin fonctionne quand la
 * protection est absente, ce qui n'est jamais le cas en production.
 */
class ContactTest extends TestCase
{
    use EmailTrait;
    use IntegrationTestTrait;

    protected const EMAIL_VISITEUR = 'visiteur-contact@test.local';

    /**
     * @return void
     */
    public function tearDown(): void
    {
        TableRegistry::getTableLocator()->get('Messages')
            ->deleteAll(['email' => self::EMAIL_VISITEUR]);

        parent::tearDown();
    }

    /**
     * Affiche le formulaire et renvoie de quoi le soumettre valablement.
     *
     * Les champs cachés sont relus dans le HTML, et la solution dans la table du
     * plugin : c'est exactement ce dont dispose un navigateur, l'un affiché et
     * l'autre déduit de l'image.
     *
     * @return array<string, mixed>
     */
    protected function ouvrirFormulaire(bool $simulerLaSaisie = true): array
    {
        $this->enableCsrfToken();
        // Le formulaire est protégé contre la falsification de champs : sans ce
        // jeton, le POST est refusé en 400 avant même d'atteindre le captcha.
        $this->enableSecurityToken();
        $this->get('/contact');
        $this->assertResponseOk('Le formulaire de contact devrait répondre.');

        $corps = (string)$this->_response->getBody();

        // Identifiant de la question, porté par un champ caché.
        preg_match('#name="captcha_uuid"[^>]*value="([^"]+)"#i', $corps, $trouve);
        $this->assertNotEmpty($trouve, 'Le formulaire devrait porter un identifiant de captcha.');
        $uuid = $trouve[1];

        // La solution n'est tirée au sort qu'au chargement de l'image : c'est le
        // navigateur qui la déclenche en affichant `<img src="…/display/{uuid}">`.
        // Sans cette requête, la question reste sans réponse en base.
        $this->get('/captcha/captcha/display/' . $uuid);
        $this->assertResponseOk("L'image du captcha devrait être servie.");

        $captchas = TableRegistry::getTableLocator()->get('Captcha.Captchas');
        $captcha = $captchas->find()->where(['uuid' => $uuid])->firstOrFail();

        if ($simulerLaSaisie) {
            // Le captcha refuse une réponse arrivée en moins de deux secondes :
            // un humain ne remplit pas un formulaire aussi vite. On antidate donc
            // la question pour simuler une saisie normale, plutôt que de
            // désactiver la règle — qui a son propre test plus bas.
            $captchas->updateAll(['created' => new DateTime('-10 seconds')], ['id' => $captcha->id]);
        }

        return [
            'corps' => $corps,
            'solution' => $captcha->result,
            'caches' => [
                'captcha_uuid' => $uuid,
                // Piège à robots du captcha passif : un humain le laisse vide.
                'email_homepage' => '',
            ],
        ];
    }

    /**
     * @return void
     */
    public function testLeFormulairePresenteTousLesChampsAttendus(): void
    {
        $ouverture = $this->ouvrirFormulaire();
        $corps = $ouverture['corps'];

        foreach (
            [
            'name="nom"' => 'nom',
            'name="email"' => 'e-mail',
            'name="demande_id"' => 'nature de la demande',
            'name="sujet"' => 'sujet',
            'name="contenu"' => 'message',
            'name="captcha_result"' => 'captcha',
            ] as $marqueur => $quoi
        ) {
            $this->assertStringContainsString(
                $marqueur,
                $corps,
                sprintf('Le champ « %s » devrait figurer au formulaire.', $quoi),
            );
        }
    }

    /**
     * Le parcours complet : captcha résolu, message en base, courriels partis.
     *
     * @return void
     */
    public function testUnMessageValideEstEnregistreEtNotifie(): void
    {
        $ouverture = $this->ouvrirFormulaire();

        $demande = TableRegistry::getTableLocator()->get('Demandes')->find()
            ->where(['actif' => true])->first();

        $this->post('/contact', $ouverture['caches'] + [
            'nom' => 'Camille Visiteur',
            'email' => self::EMAIL_VISITEUR,
            'telephone' => '0600000000',
            'sujet' => 'Devis pour un mariage',
            'demande_id' => $demande?->id,
            'contenu' => "Bonjour,\nJe cherche un photographe pour juin.",
            'captcha_result' => $ouverture['solution'],
        ]);

        $this->assertRedirect('/contact', 'Un envoi réussi redirige vers le formulaire.');

        // Stocké en base.
        $message = TableRegistry::getTableLocator()->get('Messages')->find()
            ->where(['email' => self::EMAIL_VISITEUR])
            ->first();

        $this->assertNotNull($message, 'Le message aurait dû être enregistré.');
        $this->assertSame('Camille Visiteur', $message->nom);
        $this->assertSame('Devis pour un mariage', $message->sujet);
        $this->assertStringContainsString('photographe pour juin', $message->contenu);
        $this->assertFalse($message->lu, "Un message reçu n'est pas lu.");

        // Notifié au photographe, et accusé de réception au visiteur.
        $this->assertMailCount(2);
        $this->assertMailSentToAt(0, 'dodo15@msn.com');
        $this->assertMailContainsAt(0, 'photographe pour juin');
        $this->assertMailSentWithAt(0, self::EMAIL_VISITEUR, 'replyTo');
        $this->assertMailSentToAt(1, self::EMAIL_VISITEUR);
    }

    /**
     * Un captcha faux doit tout arrêter : ni enregistrement, ni courriel.
     *
     * C'est le test qui compte, celui d'un robot : sans lui, la boîte du
     * photographe se remplirait de messages automatiques et la base avec.
     *
     * @return void
     */
    public function testUnCaptchaFauxNEnregistreRienEtNEnvoieRien(): void
    {
        $ouverture = $this->ouvrirFormulaire();

        $this->post('/contact', $ouverture['caches'] + [
            'nom' => 'Robot',
            'email' => self::EMAIL_VISITEUR,
            'sujet' => 'Publicité',
            'contenu' => 'Achetez nos produits.',
            'captcha_result' => (int)$ouverture['solution'] + 1,
        ]);

        $this->assertResponseOk('Le formulaire est réaffiché avec ses erreurs.');

        $this->assertNull(
            TableRegistry::getTableLocator()->get('Messages')->find()
                ->where(['email' => self::EMAIL_VISITEUR])->first(),
            'Aucun message ne devrait être enregistré avec un captcha faux.',
        );

        $this->assertMailCount(0, 'Aucun courriel ne devrait partir.');
    }

    /**
     * Une réponse arrivée trop vite est refusée, même si elle est juste : aucun
     * humain ne remplit ce formulaire en moins de deux secondes.
     *
     * @return void
     */
    public function testUneReponseTropRapideEstRefusee(): void
    {
        // Question laissée à sa date réelle : la réponse part dans la seconde.
        $ouverture = $this->ouvrirFormulaire(simulerLaSaisie: false);

        $this->post('/contact', $ouverture['caches'] + [
            'nom' => 'Robot rapide',
            'email' => self::EMAIL_VISITEUR,
            'contenu' => 'Message automatique.',
            'captcha_result' => $ouverture['solution'],
        ]);

        $this->assertResponseOk();
        $this->assertNull(
            TableRegistry::getTableLocator()->get('Messages')->find()
                ->where(['email' => self::EMAIL_VISITEUR])->first(),
            'Une réponse instantanée devrait être refusée malgré une solution juste.',
        );
        $this->assertMailCount(0);
    }

    /**
     * Sans captcha du tout — ce que fait un robot qui poste directement sur
     * l'URL sans avoir lu la page.
     *
     * @return void
     */
    public function testUnEnvoiSansCaptchaEstRefuse(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/contact', [
            'nom' => 'Robot',
            'email' => self::EMAIL_VISITEUR,
            'contenu' => 'Achetez nos produits.',
        ]);

        $this->assertResponseOk();
        $this->assertNull(
            TableRegistry::getTableLocator()->get('Messages')->find()
                ->where(['email' => self::EMAIL_VISITEUR])->first(),
        );
        $this->assertMailCount(0);
    }
}
