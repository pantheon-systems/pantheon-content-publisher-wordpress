<?php

namespace Pantheon\ContentPublisher;

use function add_action;
use function add_menu_page;
use function file_exists;
use function file_get_contents;
use function wp_add_inline_script;
use function wp_enqueue_script_module;
use function wp_enqueue_style;
use function wp_json_encode;
use function wp_register_style;

class Admin
{
  private string $menuSlug = 'pcc';
  private string $title = 'Pantheon Content Publisher (React)';

  public function __construct()
  {
    add_action('admin_menu', [$this, 'registerMenu']);
    add_action('admin_enqueue_scripts', [$this, 'maybeEnqueueAssets']);
  }

  public function registerMenu(): void
  {
    add_menu_page(
      $this->title,
      $this->title,
      'manage_options',
      $this->menuSlug,
      [$this, 'renderPage'],
      '',
      21
    );
  }

  public function renderPage(): void
  {
    echo '<div id="content-pub-root" style="background-color:white;"></div>';
  }

  public function maybeEnqueueAssets(): void
  {
    if (!isset($_GET['page']) || $_GET['page'] !== $this->menuSlug) {
      return;
    }

    $devServer = getenv('VITE_DEV_SERVER');
    $handle = 'pcc-admin-app';

    $this->addAdminPageStyles();

    // Enqueue assets for development server
    if (defined('WP_DEBUG') && WP_DEBUG && $devServer) {
      $protocol = is_ssl() ? 'https' : 'http';
      $clientHandle = $handle . '-client';
      $preambleHandle = $handle . '-react-refresh-preamble';
      wp_enqueue_script_module($clientHandle, "$protocol://localhost:5173/@vite/client", [], null, true);
      wp_enqueue_script_module($preambleHandle, "$protocol://localhost:5173/src/scripts/react-refresh-preamble.js", [$clientHandle], null, true);
      wp_enqueue_script_module($handle, "$protocol://localhost:5173/src/admin/main.tsx", [$clientHandle, $preambleHandle], null, true);

      $this->addBootstrap();
      return;
    }

    // Enqueue assets for production build
    $manifestPath = PCC_PLUGIN_DIR . 'dist/build/.vite/manifest.json';
    if (!file_exists($manifestPath)) {
      error_log('Manifest file not found');
      return;
    }
    $manifest = json_decode((string) file_get_contents($manifestPath), true);
    $entry = reset($manifest) ?? null;
    if (!$entry) {
      error_log('Entry not found');
      return;
    }

    $jsFile = $entry['file'] ?? null;
    $cssFiles = $entry['css'] ?? [];
    if ($jsFile) {
      wp_enqueue_script_module($handle, PCC_PLUGIN_DIR_URL . 'dist/build/' . $jsFile, [], null, true);
      $this->addBootstrap();
    }
    foreach ($cssFiles as $css) {
      wp_enqueue_style($handle, PCC_PLUGIN_DIR_URL . 'dist/build/' . $css, [], null);
    }
  }

  private function addAdminPageStyles(): void
  {
    $styleHandle = 'pcc-admin-page-styles';
    wp_register_style($styleHandle, '', [], null);
    wp_enqueue_style($styleHandle);

    $slug = $this->menuSlug;
    $css = 'body.toplevel_page_' . $slug . ' #wpwrap, '
      . 'body.toplevel_page_' . $slug . ' #wpcontent, '
      . 'body.toplevel_page_' . $slug . ' #wpbody, '
      . 'body.toplevel_page_' . $slug . ' #wpbody-content { background: #fff; } '
      . 'body.toplevel_page_' . $slug . ' #wpbody-content { padding-top: 0; } '
      . 'body.toplevel_page_' . $slug . ' #wpbody-content .wrap { margin: 0; padding: 0; }';

    wp_add_inline_style($styleHandle, $css);
  }

  private function addBootstrap(): void
  {
    $bootstrap = 'window.PCC_BOOTSTRAP = ' . wp_json_encode([
      'rest_url' => get_rest_url(get_current_blog_id(), PCC_API_NAMESPACE),
      'nonce' => wp_create_nonce('wp_rest'),
      'site_url' => site_url(),
    ]) . ';';

    wp_register_script('pcc-admin-bootstrap', '', [], null, false);
    wp_enqueue_script('pcc-admin-bootstrap');
    wp_add_inline_script('pcc-admin-bootstrap', $bootstrap, 'before');
  }
}
