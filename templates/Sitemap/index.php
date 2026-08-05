<?php
/**
 * @var \App\View\AppView $this
 * @var array $urls
 */
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url) : ?>
    <url>
        <loc><?= h($this->Url->build($url['loc'], ['fullBase' => true])) ?></loc>
        <?php if (!empty($url['lastmod'])) : ?>
        <lastmod><?= h($url['lastmod']) ?></lastmod>
        <?php endif; ?>
        <changefreq><?= h($url['changefreq'] ?? 'monthly') ?></changefreq>
        <priority><?= h($url['priority'] ?? '0.5') ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
