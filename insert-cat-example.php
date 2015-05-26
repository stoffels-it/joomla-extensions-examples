<?php
/************************
   Example Client App for inserting categories

*************************/

// Make sure we're being called from the command line, not a web interface
if (PHP_SAPI !== 'cli')
{
	die('This is a command line only application.');
}

// We are a valid entry point.
const _JEXEC = 1;

error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_LIBRARIES . '/import.legacy.php';
require_once JPATH_LIBRARIES . '/cms.php';

// Load the configuration
require_once JPATH_CONFIGURATION . '/configuration.php';

// ----------------------
class InsertCatExampleCli extends JApplicationCli {
    public function __construct() {
        parent::__construct();

        $this->config = new JConfig();
        $this->dbo = JDatabase::getInstance(
            array(
                'driver' => $this->config->dbtype,
                'host' => $this->config->host,
                'user' => $this->config->user,
                'password' => $this->config->password,
                'database' => $this->config->db,
                'prefix' => $this->config->dbprefix,
            )
        );
        // important for storing several rows
        JFactory::$application = $this;
    }

    public function doExecute() {

        $category_titles = array("Test Title 1", "Test Title 2", "Test Title 3");

        // add categories to database
        for ($i=0; $i<count($category_titles); $i++) {
            $this->insertCategory($category_titles[$i]);
        }
    }

    private function insertCategory($cat_title) {
        
        $tbl_cat = JTable::getInstance("Category");

        // get existing aliases from categories table
        $tab = $this->dbo->quoteName($this->config->dbprefix . "categories");
        $conditions = array(
            $this->dbo->quoteName("extension") . " LIKE 'com_content'"
        );


        $query = $this->dbo->getQuery(true);
        $query
            ->select($this->dbo->quoteName("alias"))
            ->from($tab)
            ->where($conditions);

        $this->dbo->setQuery($query);
        $cat_from_db = $this->dbo->loadObjectList();

        $category_existing = False;
        $new_cat_alias = JFilterOutput::stringURLSafe($cat_title);

        foreach ($cat_from_db as $cdb) {
            if ($cdb->alias == $new_cat_alias) {
                $category_existing = True;
                echo "category already existing: " . $new_cat_alias . "\n";
            }
        }
            
        // ----------------
        if (!$category_existing) {

            $values = [
                "id" => null,
                "title" => $cat_title,
                "path" => $new_cat_alias,
                "access" => 1,
                "extension" => "com_content",
                "published" => 1,
                "language" => "*",
                "created_user_id" => 0,
                "params" => array (
                    "category_layout" => "",
                    "image" => "",
                ),
                "metadata" => array (
                    "author" => "",
                    "robots" => "",
                ),
            ];

            $tbl_cat->setLocation(1, "last-child");

            if (!$tbl_cat->bind($values)) {
                JError::raiseWarning(500, $row->getError());
                return FALSE;
            }

            if (!$tbl_cat->check()) {
                JError::raiseError(500, $article->getError());
                return FALSE;
            }

            if (!$tbl_cat->store(TRUE)) {
                JError::raiseError(500, $article->getError());
                return FALSE;
            } 

            $tbl_cat->rebuildPath($tbl_cat->id); 
            echo "category inserted: " . $tbl_cat->id . " - " . $new_cat_alias . "\n";
        }
    }
}

try {
    JApplicationCli::getInstance('InsertCatExampleCli')->execute();
} 
catch (Exception $e) {
    // An exception has been caught, just echo the message.
    fwrite(STDOUT, $e->getMessage() . "\n");
    exit($e->getCode());
}

?>
