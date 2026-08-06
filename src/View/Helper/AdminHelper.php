<?php
declare(strict_types=1);

namespace App\View\Helper;

use BackedEnum;
use Cake\Chronos\ChronosDate;
use Cake\Database\Type\EnumLabelInterface;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\Utility\Hash;
use Cake\View\Helper;

/**
 * Rendu des cellules dans les listes du back-office.
 *
 * Les tableaux d'administration sont générés à partir d'une simple liste de
 * colonnes : il faut donc savoir afficher n'importe quelle valeur sans savoir à
 * l'avance ce qu'elle contient. Sans ce point de passage, chaque gabarit
 * réinventerait le formatage des dates et des booléens — et un enum, qui n'a pas
 * de `__toString()`, sortirait en `(object)`.
 */
class AdminHelper extends Helper
{
    /**
     * Marque des valeurs absentes. Un tiret cadratin se distingue à l'œil d'une
     * cellule vide, qui pourrait passer pour un défaut d'affichage.
     */
    protected const VIDE = '—';

    /**
     * Longueur au-delà de laquelle un texte est coupé dans un tableau.
     */
    protected const COUPURE = 70;

    /**
     * Valeur d'une colonne, prête à être affichée (déjà échappée).
     *
     * @param \Cake\Datasource\EntityInterface $entite Ligne courante.
     * @param string $chemin Nom de colonne, éventuellement pointé (`photo.titre`).
     * @return string
     */
    public function valeur(EntityInterface $entite, string $chemin): string
    {
        // `Hash::get` traverse la notation pointée sans lever d'erreur si un
        // maillon est nul — le cas normal d'une association facultative.
        $valeur = Hash::get($entite, $chemin);

        return $this->formater($valeur);
    }

    /**
     * @param mixed $valeur Valeur brute.
     * @return string
     */
    protected function formater(mixed $valeur): string
    {
        if ($valeur === null || $valeur === '') {
            return self::VIDE;
        }

        if (is_bool($valeur)) {
            return $valeur ? __('Oui') : __('Non');
        }

        if ($valeur instanceof EnumLabelInterface) {
            return h($valeur->label());
        }

        if ($valeur instanceof BackedEnum) {
            return h((string)$valeur->value);
        }

        if ($valeur instanceof DateTime) {
            return h($valeur->format('d/m/Y H:i'));
        }

        if ($valeur instanceof Date || $valeur instanceof ChronosDate) {
            return h($valeur->format('d/m/Y'));
        }

        // Une association : on affiche son libellé plutôt que son identifiant,
        // qui ne dit rien à qui lit la liste.
        if ($valeur instanceof EntityInterface) {
            return $this->formater(
                $valeur->get('nom') ?? $valeur->get('titre') ?? $valeur->get('email') ?? $valeur->get('id'),
            );
        }

        if (is_array($valeur)) {
            return h((string)count($valeur));
        }

        return h($this->couper((string)$valeur));
    }

    /**
     * @param string $texte Texte à raccourcir.
     * @return string
     */
    protected function couper(string $texte): string
    {
        // `strip_tags` d'abord : plusieurs colonnes portent du HTML éditorial, et
        // couper au milieu d'une balise produirait un fragment invalide.
        $texte = trim(strip_tags($texte));

        if (mb_strlen($texte) <= self::COUPURE) {
            return $texte;
        }

        return mb_substr($texte, 0, self::COUPURE) . '…';
    }
}
