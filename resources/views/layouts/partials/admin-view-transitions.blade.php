<style>
    @view-transition {
        navigation: auto;
    }

    .admin-sidebar-desktop {
        view-transition-name: admin-sidebar;
    }

    .admin-topbar {
        view-transition-name: admin-topbar;
    }

    .admin-page-content {
        view-transition-name: admin-content;
    }

    ::view-transition-old(root),
    ::view-transition-new(root) {
        animation-duration: 180ms;
        animation-timing-function: ease-out;
    }

    ::view-transition-old(admin-sidebar),
    ::view-transition-new(admin-sidebar),
    ::view-transition-old(admin-topbar),
    ::view-transition-new(admin-topbar) {
        animation: none;
        mix-blend-mode: normal;
    }

    ::view-transition-old(admin-content) {
        animation: admin-content-out 120ms ease-in both;
    }

    ::view-transition-new(admin-content) {
        animation: admin-content-in 180ms ease-out both;
    }

    @keyframes admin-content-out {
        to {
            opacity: 0;
            transform: translateY(3px);
        }
    }

    @keyframes admin-content-in {
        from {
            opacity: 0;
            transform: translateY(5px);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        ::view-transition-old(root),
        ::view-transition-new(root),
        ::view-transition-old(admin-content),
        ::view-transition-new(admin-content) {
            animation-duration: 1ms;
        }
    }
</style>
