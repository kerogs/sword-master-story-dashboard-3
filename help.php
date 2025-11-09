<?php

require_once __DIR__ . '/inc/core.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <base href="/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMSDV3 | Help</title>
    <link rel="stylesheet" href="assets/styles/css/style.css">
    <link rel="shortcut icon" href="./assets/img/favicon.png" type="image/x-icon">

    <?php require_once __DIR__ . '/inc/head.php' ?>
</head>

<body>
    <?php require_once __DIR__ . '/inc/aside.php'; ?>

    <main>
        <?php
        // Charger les données d'aide
        $helpData = json_decode(file_get_contents(__DIR__ . '/backend/help.json'), true);
        $currentUri = $_SERVER['REQUEST_URI'];
        $isSingleView = strpos($currentUri, 'help/') !== false;

        if ($isSingleView) {
            $helpSlug = basename($currentUri);
            displaySingleHelp($helpData, $helpSlug);
        } else {
            displayAllHelp($helpData);
        }

        function displayAllHelp($helpData)
        {
        ?>
            <div class="help-page">
                <div class="help-container">
                    <div class="help-header">
                        <h1>Help Center</h1>
                        <p>Find answers to your questions and get the most out of SMSDV3</p>
                    </div>

                    <div class="help-search">
                        <input type="text" id="helpSearch" class="search-input" placeholder="Search for help...">
                    </div>

                    <div class="help-items" id="helpList">
                        <?php foreach ($helpData as $index => $item):
                            $slug = createSlug($item['title']);
                        ?>
                            <div class="help-item"
                                data-tags="<?php echo htmlspecialchars(json_encode($item['tag'])); ?>"
                                data-slug="<?php echo $slug; ?>">
                                <div class="help-header">
                                    <div class="help-title-section">
                                        <h3><?php echo $item['title']; ?></h3>
                                        <div class="help-tags">
                                            <?php foreach ($item['tag'] as $tag): ?>
                                                <span class="tag"><?php echo $tag; ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="help-arrow">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="help-content">
                                    <div class="content-inner">
                                        <div class="help-text">
                                            <?php echo $item['content']; ?>
                                        </div>
                                        <div class="full-page-link">
                                            <a href="/help/<?php echo $slug; ?>">
                                                Open full page
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left: 0.5rem;">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php
        }

        function displaySingleHelp($helpData, $helpSlug)
        {
            $currentHelp = null;
            foreach ($helpData as $item) {
                if (createSlug($item['title']) === $helpSlug) {
                    $currentHelp = $item;
                    break;
                }
            }

            if (!$currentHelp) {
                echo '<div class="help-page">';
                echo '<div class="help-container">';
                echo '<div class="help-not-found">';
                echo '<h1>Help Article Not Found</h1>';
                echo '<p>The help article you\'re looking for doesn\'t exist.</p>';
                echo '<a href="/help" class="back-button">Back to Help Center</a>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                return;
            }
        ?>
            <div class="help-page">
                <div class="help-container">
                    <div class="single-help">
                        <a href="/help" class="back-button">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Help Center
                        </a>

                        <article class="help-article">
                            <div class="article-tags">
                                <?php foreach ($currentHelp['tag'] as $tag): ?>
                                    <span class="tag"><?php echo $tag; ?></span>
                                <?php endforeach; ?>
                            </div>
                            <h1><?php echo $currentHelp['title']; ?></h1>
                            <div class="article-content">
                                <?php echo $currentHelp['content']; ?>
                            </div>
                        </article>

                        <div class="recommendations">
                            <h2>Related Help Articles</h2>
                            <div class="recommendation-grid" id="recommendations">
                                <?php
                                $recommendations = getRelatedHelp($helpData, $currentHelp, 4);
                                foreach ($recommendations as $item):
                                    $slug = createSlug($item['title']);
                                ?>
                                    <a href="/help/<?php echo $slug; ?>" class="recommendation-card">
                                        <h3><?php echo $item['title']; ?></h3>
                                        <div class="card-tags">
                                            <?php foreach ($item['tag'] as $tag): ?>
                                                <span class="tag"><?php echo $tag; ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }

        function createSlug($title)
        {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $title), '-'));
            // Cas spéciaux pour les URLs communes
            $commonSlugs = [
                'github-project' => 'github',
                'report-a-bug' => 'report-bug',
                'feature-request' => 'feature-request',
                'frequently-asked-questions' => 'faq',
                'api-documentation' => 'api-docs'
            ];
            return $commonSlugs[$slug] ?? $slug;
        }

        function getRelatedHelp($helpData, $currentHelp, $limit = 4)
        {
            $related = [];
            $currentTags = $currentHelp['tag'];
            $currentTitle = $currentHelp['title'];

            foreach ($helpData as $item) {
                if ($item['title'] === $currentTitle) continue;

                $commonTags = array_intersect($currentTags, $item['tag']);
                $score = count($commonTags);

                if ($score > 0) {
                    $related[] = [
                        'item' => $item,
                        'score' => $score
                    ];
                }
            }

            // Trier par score décroissant
            usort($related, function ($a, $b) {
                return $b['score'] - $a['score'];
            });

            // Retourner les $limit premiers
            $result = array_slice($related, 0, $limit);
            return array_map(function ($r) {
                return $r['item'];
            }, $result);
        }
        ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion de l'expansion des articles
            const helpItems = document.querySelectorAll('.help-item');

            helpItems.forEach(item => {
                const header = item.querySelector('.help-header');
                const content = item.querySelector('.help-content');
                const arrow = item.querySelector('.help-arrow');

                header.addEventListener('click', () => {
                    const isExpanded = content.classList.contains('expanded');

                    // Fermer tous les autres
                    helpItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.querySelector('.help-content').classList.remove('expanded');
                            otherItem.querySelector('.help-arrow').classList.remove('expanded');
                        }
                    });

                    // Basculer l'état actuel
                    content.classList.toggle('expanded', !isExpanded);
                    arrow.classList.toggle('expanded', !isExpanded);
                });
            });

            // Filtrage par catégorie
            const filterButtons = document.querySelectorAll('.filter-btn');
            const helpItemsAll = document.querySelectorAll('.help-item');

            filterButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const filter = button.getAttribute('data-filter');

                    // Mettre à jour les boutons actifs
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');

                    // Filtrer les éléments
                    helpItemsAll.forEach(item => {
                        if (filter === 'all') {
                            item.style.display = 'block';
                        } else {
                            const tags = JSON.parse(item.getAttribute('data-tags'));
                            if (tags.includes(filter)) {
                                item.style.display = 'block';
                            } else {
                                item.style.display = 'none';
                            }
                        }
                    });
                });
            });

            // Recherche en temps réel
            const searchInput = document.getElementById('helpSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();

                    helpItemsAll.forEach(item => {
                        const title = item.querySelector('h3').textContent.toLowerCase();
                        const content = item.querySelector('.help-text').textContent.toLowerCase();
                        const tags = JSON.parse(item.getAttribute('data-tags')).join(' ').toLowerCase();

                        if (title.includes(searchTerm) || content.includes(searchTerm) || tags.includes(searchTerm)) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>
<?php require_once __DIR__ . '/inc/scripts.php'; ?>

</html>