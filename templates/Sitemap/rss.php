<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $articles
 */
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>Chancel Photographie — Journal</title>
        <link><?= h($this->Url->build('/blog', ['fullBase' => true])) ?></link>
        <description>Carnets de route, séries et coulisses.</description>
        <language>fr-FR</language>
        <atom:link href="<?= h($this->Url->build('/rss', ['fullBase' => true])) ?>" rel="self" type="application/rss+xml" />
<?php foreach ($articles as $article) : ?>
        <item>
            <title><?= h($article->titre) ?></title>
            <link><?= h($this->Url->build('/blog/' . $article->slug, ['fullBase' => true])) ?></link>
            <?php // Le GUID doit être stable dans le temps : c'est lui qui dit au lecteur si l'article est déjà connu. ?>
            <guid isPermaLink="true"><?= h($this->Url->build('/blog/' . $article->slug, ['fullBase' => true])) ?></guid>
            <pubDate><?= h($article->publie_le?->format(DATE_RSS)) ?></pubDate>
            <description><?= h((string)($article->chapeau ?: '')) ?></description>
        </item>
<?php endforeach; ?>
    </channel>
</rss>
