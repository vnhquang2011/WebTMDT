<?php namespace Premmerce\Search;

use Premmerce\Search\Admin\Admin;
use Premmerce\Search\Frontend\RestController;
use Premmerce\Search\Frontend\SearchHandler;
use Premmerce\Search\Model\Word;
use Premmerce\SDK\V2\FileManager\FileManager;
use Premmerce\SDK\V2\Notifications\AdminNotifier;
use Premmerce\SDK\V2\Plugin\PluginInterface;

/**
 * Class SearchPlugin
 *
 * @package Premmerce\Search
 */
class SearchPlugin implements PluginInterface
{
    const DOMAIN = 'premmerce-search';

    const OPTIONS = array(
        'minToSearch'   => 'premmerce_search_min_symbols_to_search',
        'resultNum'     => 'premmerce_search_results_num',
        'whereToSearch' => 'premmerce_search_fields',
    );

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var Word
     */
    private $word;

    /**
     * @var WordProcessor
     */
    private $wordProcessor;

    /**
     * @var AdminNotifier
     */
    private $notifier;

    /**
     * @var Admin
     */
    private $admin;

    /**
     * PluginManager constructor.
     *
     * @param string $mainFile
     */
    public function __construct($mainFile)
    {
        $this->fileManager = new FileManager($mainFile, 'premmerce-search');
        $this->notifier    = new AdminNotifier();

        $this->word          = new Word();
        $this->wordProcessor = new WordProcessor();

        add_action('init', array($this, 'loadTextDomain'));
        add_action('admin_init', array($this, 'checkRequirePlugins'));
    }

    /**
     * Run plugin part
     */
    public function run()
    {
        $valid = count($this->validateRequiredPlugins()) === 0;

        if (is_admin()) {
            $this->admin = new Admin($this->fileManager, $this->word, $this->wordProcessor);
        } elseif ($valid) {
            new SearchHandler($this->word, $this->wordProcessor, $this->fileManager);
            new RestController($this->fileManager);
        }
    }

    /**
     * Fired when the plugin is activated
     */
    public function activate()
    {
        $this->word->createTable();
        $this->admin->setSettings();
    }

    /**
     * Fired during plugin uninstall
     */
    public static function uninstall()
    {
        (new Word())->dropTable();

        foreach (self::OPTIONS as $optionName) {
            delete_option($optionName);
        }
    }

    /**
     * Check required plugins and push notifications
     */
    public function checkRequirePlugins()
    {
        $message = __('The %s plugin requires %s plugin to be active!', 'premmerce-search');

        $plugins = $this->validateRequiredPlugins();

        if (count($plugins)) {
            foreach ($plugins as $plugin) {
                $error = sprintf($message, 'Premmerce Search', $plugin);
                $this->notifier->push($error, AdminNotifier::ERROR, false);
            }
        }
    }

    /**
     * Validate required plugins
     *
     * @return array
     */
    private function validateRequiredPlugins()
    {
        $plugins = array();

        if (! function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        /**
         * Check if WooCommerce is active
         **/
        if (! (is_plugin_active('woocommerce/woocommerce.php') || is_plugin_active_for_network('woocommerce/woocommerce.php'))) {
            $plugins[] = '<a target="_blank" href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a>';
        }

        return $plugins;
    }

    /**
     * Load plugin translations
     */
    public function loadTextDomain()
    {
        $name = $this->fileManager->getPluginName();
        load_plugin_textdomain(self::DOMAIN, false, $name . '/languages/');
    }

    /**
     * Fired when the plugin is deactivated
     */
    public function deactivate()
    {
    }
}
