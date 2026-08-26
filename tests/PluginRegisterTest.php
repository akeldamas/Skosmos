<?php

class PluginRegisterTest extends PHPUnit\Framework\TestCase
{
    private $model;
    private $concept;
    private $mockpr;
    private $stubplugs;
    private $vocab;

    protected function setUp(): void
    {
        $this->mockpr = $this->getMockBuilder('PluginRegister')->setConstructorArgs(array(array('test-plugin2',
                                                                                                'global-plugin-Bravo',
                                                                                                'imaginary-plugin',
                                                                                                'test-plugin1',
                                                                                                'global-plugin-alpha',
                                                                                                'global-plugin-charlie',
                                                                                                'test-plugin3'
                                                                                                )))
                                                               ->onlyMethods(['getPlugins'])
                                                               ->getMock();
        $this->stubplugs = array('imaginary-plugin' => array( 'js' => array( 0 => 'imaginaryPlugin.js', ),
                                                     'css' => array( 0 => 'stylesheet.css', ),
                                                     'callback' => array( 0 => 'imaginaryPlugin')
                            ),
                            'test-plugin1' => array( 'js' => array( 0 => 'plugin1.js', 1 => 'second.min.js'),
                                                     'css' => array( 0 => 'stylesheet.css', ),
                                                     'callback' => array( 0 => 'callplugin1', 1 => 'secondplugin')  // multiple callbacks to test overwrite bug
                            ),
                            'test-plugin2' => array( 'js' => array( 0 => 'plugin2.js', ),
                                                     'css' => array( 0 => 'stylesheet.css', )
                                                     // NO callback entry to test numeric index leak bug
                            ),
                            'test-plugin3' => array( 'js' => array( 0 => 'plugin3.js', ),
                                                     'css' => array( 0 => 'stylesheet.css', ),
                                                     'callback' => array( 0 => 'callplugin3')
                            ),
                            'only-css' => array( 'css' => array( 0 => 'super.css')),
                            'global-plugin-alpha' => array('js' => array('alpha.js'),
                                                      'callback' => array( 0 => 'alpha')),
                            'global-plugin-Bravo' => array('js' => array('Bravo.js'),
                                                      'callback' => array( 0 => 'bravo')),
                            'global-plugin-charlie' => array('js' => array('charlie.js'),
                                                      'callback' => array( 0 => 'charlie')));
        $this->mockpr->method('getPlugins')->will($this->returnValue($this->stubplugs));
        $this->model = new Model();
        $this->vocab = $this->model->getVocabulary('test');
    }

    /**
     * @covers PluginRegister::__construct
     */
    public function testConstructor()
    {
        $plugins = new PluginRegister();
        $this->assertInstanceOf('PluginRegister', $plugins);
    }

    /**
     * @covers PluginRegister::getPlugins
     * @covers PluginRegister::getPluginsJS
     */
    public function testGetPluginsJS()
    {
        $plugins = new PluginRegister();
        $this->assertEquals(array(), $plugins->getPluginsJS());
    }

    /**
     * @covers PluginRegister::getPlugins
     * @covers PluginRegister::getPluginsJS
     * @covers PluginRegister::filterPlugins
     * @covers PluginRegister::filterPluginsByName
     * @covers PluginRegister::sortPlugins
     */
    public function testGetPluginsJSInOrder()
    {
        $this->assertEquals(
            ['test-plugin2',
                             'global-plugin-Bravo',
                             'imaginary-plugin',
                             'test-plugin1',
                             'global-plugin-alpha',
                             'global-plugin-charlie',
                             'test-plugin3'],
            array_keys($this->mockpr->getPluginsJS())
        );

    }

    /**
     * @covers PluginRegister::getPlugins
     * @covers PluginRegister::getPluginsJS
     * @covers PluginRegister::filterPlugins
     * @covers PluginRegister::filterPluginsByName
     * @covers PluginRegister::sortPlugins
     */
    public function testGetPluginsJSWithName()
    {
        $this->assertEquals(
            array('plugins/test-plugin1/plugin1.js', 'plugins/test-plugin1/second.min.js'),
            $this->mockpr->getPluginsJS()['test-plugin1']
        );
    }

    /**
     * @covers PluginRegister::getPlugins
     * @covers PluginRegister::getPluginsJS
     * @covers PluginRegister::filterPlugins
     * @covers PluginRegister::filterPluginsByName
     * @covers PluginRegister::sortPlugins
     */
    public function testGetPluginsJSWithGlobalPlugin()
    {
        $this->assertEquals(
            array('plugins/global-plugin-alpha/alpha.js'),
            $this->mockpr->getPluginsJS()['global-plugin-alpha']
        );
    }

    /**
     * @covers PluginRegister::getPluginsCSS
     */
    public function testGetPluginsCSS()
    {
        $plugins = new PluginRegister();
        $this->assertEquals(array(), $plugins->getPluginsCSS());
    }

    /**
     * @covers PluginRegister::getPluginsCSS
     * @covers PluginRegister::filterPlugins
     * @covers PluginRegister::filterPluginsByName
     * @covers PluginRegister::sortPlugins
     */
    public function testGetPluginsCSSWithName()
    {
        $this->assertEquals(
            array('plugins/test-plugin1/stylesheet.css'),
            $this->mockpr->getPluginsCSS()['test-plugin1']
        );
    }

    /**
     * @covers PluginRegister::getPluginCallbacks
     * @covers PluginRegister::filterPlugins
     * @covers PluginRegister::filterPluginsByName
     * @covers PluginRegister::sortPlugins
     */
    public function testGetPluginCallbacks()
    {
        $plugins = new PluginRegister();
        // test-plugin1 now has TWO callbacks - verify both are returned
        $this->assertEquals(
            array('plugins/test-plugin1/callplugin1', 'plugins/test-plugin1/secondplugin'),
            $this->mockpr->getPluginCallbacks()['test-plugin1']
        );
        // test-plugin2 has NO callback entry - should be absent from results
        $this->assertArrayNotHasKey('test-plugin2', $this->mockpr->getPluginCallbacks());
    }

    /**
     * @covers PluginRegister::getCallbacks
     */
    public function testGetCallbacks()
    {
        // test-plugin2 has NO callback entry, so it should NOT appear in callbacks
        // test-plugin1 has TWO callbacks, both should be preserved
        $this->assertEquals(
            array('bravo', 'imaginaryPlugin', 'callplugin1', 'secondplugin', 'alpha', 'charlie', 'callplugin3'),
            $this->mockpr->getCallbacks()
        );
    }

    /**
     * @covers PluginRegister::getCallbacks
     * Tests that multiple callbacks per plugin are all preserved, not overwritten
     */
    public function testGetCallbacksMultiplePerPlugin()
    {
        // Verify that test-plugin1's second callback 'secondplugin' is included
        $callbacks = $this->mockpr->getCallbacks();
        $this->assertContains('callplugin1', $callbacks, 'First callback for test-plugin1 should be present');
        $this->assertContains('secondplugin', $callbacks, 'Second callback for test-plugin1 should be present (not overwritten)');
    }

    /**
     * @covers PluginRegister::getCallbacks
     * Tests that plugins without callback entry don't cause numeric indices to leak
     */
    public function testGetCallbacksNoNumericLeak()
    {
        $callbacks = $this->mockpr->getCallbacks();
        // Verify no numeric values leak into the result
        foreach ($callbacks as $callback) {
            $this->assertIsString($callback, "Callback value should be a string, got: " . var_export($callback, true));
        }
    }

}
