/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

$(() => {
  // Prevent TinyMCE from wrapping content in <p> tags,
  // which corrupts email template HTML structure.
  window.prestashop.component.EventEmitter.on(
    'initTinyMCE',
    (event: {config: Record<string, unknown>}) => {
      event.config.forced_root_block = false;
      event.config.verify_html = false;
    },
  );

  window.prestashop.component.initComponents([
    'TinyMCEEditor',
  ]);
});
