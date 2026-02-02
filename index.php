<?php
require_once 'includes/data.php';
$igConf = function_exists('getInstagramConfig') ? getInstagramConfig() : ['username'=>'','access_token'=>'','post_limit'=>6];
$posts = function_exists('fetchInstagramPosts') ? fetchInstagramPosts(intval($igConf['post_limit'] ?? 6)) : [];
include 'includes/header.php';
?>

<div class="container">
    <h1 class="page-title">Últimas do Instagram</h1>
    
    <div class="insta-grid">
        <?php if (!empty($posts)): ?>
            <?php foreach ($posts as $p): ?>
                <div class="insta-item">
                    <div class="insta-img-wrapper">
                        <a href="<?php echo htmlspecialchars($p['permalink']); ?>" target="_blank" rel="noopener">
                            <?php
                            $srcRaw = isset($p['media_url']) && $p['media_url'] ? $p['media_url'] : '';
                            $src = $srcRaw ? ('ig_image.php?u=' . urlencode($srcRaw)) : "https://placehold.co/400x400/E0B0B6/white?text=Ballet+Post";
                            ?>
                            <img src="<?php echo $src; ?>" alt="Instagram Post">
                        </a>
                    </div>
                    <div class="insta-caption">
                        <p><?php echo htmlspecialchars($p['caption'] ?? ''); ?></p>
                    </div>
                    <div class="insta-meta">
                        <?php
                        $ts = intval($p['timestamp'] ?? 0);
                        $dateStr = $ts ? date('d/m/Y', $ts) : '';
                        ?>
                        <span><?php echo htmlspecialchars($dateStr); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php for($i=1; $i<=intval($igConf['post_limit'] ?? 6); $i++): ?>
            <div class="insta-item">
                <div class="insta-img-wrapper">
                    <img src="https://placehold.co/400x400/E0B0B6/white?text=Ballet+Post+<?php echo $i; ?>" alt="Instagram Post <?php echo $i; ?>">
                </div>
                <div class="insta-caption">
                    <p>Configure o Instagram no Dashboard para exibir os posts.</p>
                </div>
            </div>
            <?php endfor; ?>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: 40px;">
        <?php
        $igUserLink = isset($igConf['username']) ? preg_replace('/^@/', '', $igConf['username']) : '';
        ?>
        <a href="https://instagram.com/<?php echo htmlspecialchars($igUserLink); ?>" target="_blank" class="btn" style="max-width: 200px;">Ver mais no Instagram</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
