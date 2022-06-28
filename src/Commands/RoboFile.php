<?php
namespace WPLib_CLI\Commands;

use Twig\Environment;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    /**
     * @var string
     */
    protected $_namespace = '';

    /**
     * The relative path to the mu-plugin directory
     *
     * @var string
     */
    protected $_appName = '';

    /**
     * @var string
     */
    protected $_shortPrefix = '';

    /**
     * @var string
     */
    protected $_textDomain = '';

    /**
     *
     */
    public function __construct()
    {
        if ( file_exists( WORKING_DIR . '/wplib.json' ) ) {
            $params = json_decode( file_get_contents( WORKING_DIR . '/wplib.json' ), 'associative_array' );

            if ( $params ) {
                $this->_set_state( $params );
            }
        }
    }

    /**
     * A method to scaffold a new post type
     *
     * @param string $slug
     * @param string $singular
     * @param string|null $plural
     * @return void
     */
    public function createPostType( string $slug = '', string $singular = '', string $plural = null ) : void
    {
        $params = [
            'slug'     => ['value' => $slug, 'label' => 'Enter value for slug (e.g. post):'],
            'singular' => ['value' => $singular, 'label' => 'Enter value for singular label (e.g. Post):'],
            'plural'   => ['value' => $plural, 'label' => 'Enter value for plural label (e.g. Posts):'],
        ];

        foreach ($params as $key => $value ) {
            if ( empty( $value['value'] ) ) {
                $params[$key]['value'] = $this->ask( $value['label'] );
            }
        }

        foreach( $params as $key => $value ) {
            $params[$key] = $value['value'];
        }

        $slug = $params['slug'];
        $module_path = $this->module_directory() . '/post-type-' . $slug;

        $twig   = $this->_twig();
        $params = array_merge($params, $this->_global_params());

        try {
            $collection = $this->collectionBuilder();

            $collection->taskFilesystemStack()
                ->mkdir( $module_path )
                ->mkdir( "{$module_path}/includes" )
                ->touch( "{$module_path}/post-type-{$slug}.php" )
                ->touch( "{$module_path}/includes/class-{$slug}.php" )
                ->touch( "{$module_path}/includes/class-{$slug}-model.php" )
                ->touch( "{$module_path}/includes/class-{$slug}-view.php" );

            $collection->taskWriteToFile("{$module_path}/post-type-{$slug}.php")
                ->text($twig->render('post-type.twig', $params));

            $collection->taskWriteToFile("{$module_path}/includes/class-{$slug}.php")
                ->text($twig->render('post-instance.twig', $params));

            $collection->taskWriteToFile("{$module_path}/includes/class-{$slug}-model.php")
                ->text($twig->render('post-instance-model.twig', $params));

            $collection->taskWriteToFile("{$module_path}/includes/class-{$slug}-view.php")
                ->text($twig->render('post-instance-view.twig', $params));

            $collection->run();
        } catch (Exception $e) {
            $this->say( 'Error creating post type definition and instance classes.' );
        }
    }

    /**
     * @param string $appName
     * @param string $shortPrefix
     * @param string $textDomain
     */
    public function init(string $appName = '', string $shortPrefix = '', string $textDomain = '') {
        $params = [
            'appName'       => ['value' => $appName,     'label' => 'Enter value for app name (e.g. MyProject, My_Project, but not \'My Project\'):'],
            'shortPrefix'   => ['value' => $shortPrefix, 'label' => 'Enter value for short prefix (e.g. mp):'],
            'textDomain'    => ['value' => $textDomain,  'label' => 'Enter the text domain for gettext functions:'],
        ];

        foreach ($params as $key => $value ) {
            if ( empty( $value['value'] ) ) {
                $params[$key]['value'] = $this->ask( $value['label'] );
            }
        }

        foreach( $params as $key => $value ) {
            $params[$key] = $value['value'];
        }

        $collection = $this->collectionBuilder();

        $appName = $params['appName'];

        $muPluginDir = getcwd() . '/wp-content/mu-plugins/';
        $appFileDir  = $muPluginDir. strtolower($appName);
        $appFile     = strtolower($appFileDir . "/{$appName}.php");

        $collection->addTask($this->taskFilesystemStack()
            ->touch(getcwd() . '/wplib.json')
            ->mkdir($appFileDir)
            ->touch($appFile)
        );

        $collection->addTask($this->taskWriteToFile(getcwd() . '/wplib.json')->text(json_encode($params)));

        $collection->addTask(
            $this->taskWriteToFile( $appFile  )
                ->text($this->_twig()->render('app.twig', $params))
        );

        $collection->addTask(
            $this->taskFilesystemStack()
                ->touch($muPluginDir . '/plugin-loader.php')
        );

        $params['appNameUppercase'] = strtoupper($appName);
        $params['appNameLowercase'] = strtolower($appName);

        $collection->addTask(
            $this->taskWriteToFile($muPluginDir . '/plugin-loader.php')
                ->text($this->_twig()->render('plugin-loader.twig', $params))
        );

        $collection->run();
    }

    /**
     * @return string
     */
    protected function module_directory(): string
    {
        return getcwd() . '/' . $this->_plugin_dir . '/modules';
    }

    /**
     * @param $params
     * @return void
     */
    protected function _set_state( array $params ): void
    {
        foreach( $params as $key => $val ) {
            $name = "_{$key}";

            switch ( property_exists( $this, $name ) ) {

                case true :
                    $this->{$name} = $val;
                    break;
                default :
                    $this->_extra_args[$name] =  $val;
            }
        }
    }

    /**
     * @return array
     */
    protected function _global_params() : array
    {
        return [
            'namespace'    => $this->_namespace,
            'short_prefix' => $this->_short_prefix,
            'text_domain'  => $this->_text_domain,
        ];
    }

    /**
     * @return Environment
     */
    protected function _twig() : Environment
    {
        $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__) . '/templates' );
        return new \Twig\Environment($loader);
    }


}
