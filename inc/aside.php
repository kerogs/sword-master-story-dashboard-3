<?php
$pageName = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" _="on click toggle .active on #mobile-overlay then toggle .active on .aside then toggle .shy on .mobile-menu-btn">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobile-overlay" _="on click remove .active from me then remove .active from .aside then toggle .shy on .mobile-menu-btn"></div>

<aside class="aside">
    <button class="close-sidebar" _="on click remove .active from #mobile-overlay then remove .active from .aside then toggle .shy on .mobile-menu-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
    </button>

    <div class="title">
        <img src="./assets/img/favicon.png" alt="">
        <h1>SMS<span>DV3</span></h1>
    </div>

    <nav>
        <ul>
            <a class="<?= $pageName == 'index.php' ? 'active' : '' ?>" href="/">
                <li>Dashboards</li>
            </a>
            <a href="/coupons">
                <li>Coupons</li>
            </a>
            <?php if ($auth->isLoggedIn()) { ?>
                <a href="/profile/<?= $auth->getCurrentUser()['username']; ?>">
                    <li>Profile</li>
                </a>
                <a href="profile-edit">
                    <li>Settings</li>
                </a>
            <?php } else { ?>
                <a href="/auth" class="login" >
                    <li>Login</li>
                </a>
            <?php } ?>
            <a class="<?= $pageName == 'help.php' ? 'active' : '' ?>" href="/help">
                <li>Help</li>
            </a>
            <?php if ($auth->isLoggedIn()) { ?>
                <a href="/logout" class="logout">
                    <li>Logout</li>
                </a>
            <?php } ?>
        </ul>
    </nav>

    <div class="footerAside">
        <p>version <?= smsdv3_version ?></p>
        <p class="c">&copy; 2025 <a href="https://github.com/kerogs" target="_blank">kerogs'</a> & contributors
            &bull; <a href="https://github.com/kerogs/SMSDV3">SMSDV3</a>. Licensed under MIT</p>
    </div>
</aside>