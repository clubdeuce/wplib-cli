<?php

namespace wplibcli\Commands;

require_once '../..//vendor/autoload.php';

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
    protected $_plugin_dir = '';

    /**
     * @var string
     */
    protected $_short_prefix = '';

    /**
     * @var string
     */
    protected $_text_domain = '';

    /**
     *
     */
    public function __construct()
    {
        if ( file_exists( __DIR__ . '/wplib.json' ) ) {
            $params = json_decode( file_get_contents( __DIR__ . '/wplib.json' ), 'associative_array' );

            if ( $params ) {
                $this->_set_state( $params );
            }
        }
    }

    // define public methods as commands

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
                $params[$key] = $this->ask( $value['label'] );
            }
        }

        $slug        = $params['slug'];
        $module_path = $this->module_directory() . '/post-type-' . $slug;

        // create  the post type module directory
        $this->taskFilesystemStack()
            ->mkdir( $module_path )
            ->mkdir( "{$module_path}/includes" )
            ->touch( "{$module_path}/post-type-{$slug}.php" )
            ->touch( "{$module_path}/includes/class-{$slug}.php" )
            ->touch( "{$module_path}/includes/class-{$slug}-model.php" )
            ->touch( "{$module_path}/includes/class-{$slug}-view.php" )
            ->run();

        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates' );
        $twig   = new \Twig\Environment($loader);
        $params = array_merge($params, $this->_global_params());

        try {
            $this->taskWriteToFile("{$module_path}/post-type-{$slug}.php")
                ->text($twig->render('post-type.twig', $params))
                ->run();
        } catch (Exception $e) {
            $this->say( 'Error creating post type definition' );
        }

        try {
            $this->taskWriteToFile("{$module_path}/includes/class-{$slug}.php")
                ->text($twig->render('post-instance.twig', $params))
                ->run();
        } catch (Exception $e) {
            $this->say( 'Error creating post type controller' );
        }

        try {
            $this->taskWriteToFile("{$module_path}/includes/class-{$slug}-model.php")
                ->text($twig->render('post-instance-model.twig', $params))
                ->run();
        } catch (Exception $e) {
            $this->say( 'Error creating post type model' );
        }

        try {
            $this->taskWriteToFile("{$module_path}/includes/class-{$slug}-view.php")
                ->text($twig->render('post-instance-view.twig', $params))
                ->run();
        } catch (Exception $e) {
            $this->say( 'Error creating post type view' );
        }
    }

    /**
     * @return string
     */
    protected function module_directory(): string
    {
        return __DIR__ . '/' . $this->_plugin_dir . '/modules';
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

}
