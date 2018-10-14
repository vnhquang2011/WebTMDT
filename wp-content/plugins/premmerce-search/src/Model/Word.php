<?php namespace Premmerce\Search\Model;

use wpdb;

class Word
{
    /**
     * @var wpdb
     */
    private $wpdb;

    /**
     * @var string
     */
    private $tableName;

    /**
     * Word constructor.
     */
    public function __construct()
    {
        global $wpdb;

        $this->wpdb      = $wpdb;
        $this->tableName = $wpdb->prefix . 'premmerce_search_words';
    }

    /**
     * @return array
     */
    public function getWords()
    {
        $words = $this->wpdb->get_col("SELECT `word` FROM {$this->tableName}");

        return $words;
    }

    /**
     * Update words list in database
     *
     * @param $words
     *
     * @return false|int
     */
    public function updateIndexes($words)
    {
        $this->truncateWords();

        $placeholders = str_repeat("('%s'),", count($words));

        $placeholders = trim($placeholders, ',');

        $query = $this->wpdb->prepare("INSERT INTO {$this->tableName} (`word`) VALUES {$placeholders}", $words);

        $result = $this->wpdb->query($query);

        return $result;
    }

    /**
     * Truncate words table
     * @return false|int
     */
    public function truncateWords()
    {
        return $this->wpdb->query("TRUNCATE TABLE {$this->tableName}");
    }

    /**
     * Returns all words
     * @return array
     */
    public function selectProductWords()
    {
        $query = $this->wpdb->prepare(
            "SELECT `post_title` FROM {$this->wpdb->posts} WHERE `post_type` IN" . '(%s, %s)',
            'product',
            'product_variation'
        );

        $titles = $this->wpdb->get_col($query, 0);

        return $titles;
    }

    /**
     * Create database table
     *
     * @return false|int
     */
    public function createTable()
    {
        $charsetCollate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->tableName} (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `word` varchar(255),
            PRIMARY KEY  (id)
            ) {$charsetCollate}";

        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        return dbDelta($sql);
    }

    /**
     * Drop database table
     *
     * @return false|int
     */
    public function dropTable()
    {
        $query = $this->generateDropTableQuery($this->tableName);

        return $this->wpdb->query($query);
    }

    /**
     * Generates sql string for dropping table
     *
     * @param string $name
     *
     * @return string
     */
    private function generateDropTableQuery($name)
    {
        $query = sprintf('DROP TABLE IF EXISTS %s', $name);

        return $query;
    }
}
