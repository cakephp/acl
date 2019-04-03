<?php
/**
 * Acl Extras Shell.
 *
 * Enhances the existing Acl Shell with a few handy functions
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2008-2013, Mark Story.
 * @link http://mark-story.com
 * @author Mark Story <mark@mark-story.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
namespace Acl\Shell;

use Acl\AclExtras;
use Cake\Console\ConsoleIo;
use Cake\Console\Shell;
use Cake\Database\Exception;
use Cake\ORM\TableRegistry;

/**
 * Shell for ACO extras
 */
class AclExtrasShell extends Shell
{

    /**
     * Contains arguments parsed from the command line.
     *
     * @var array
     */
    public $args;

    /**
     * AclExtras instance
     *
     * @var AclExtras
     */
    public $AclExtras;

    /**
     * Constructor
     *
     * @param \Cake\Console\ConsoleIo $io An io instance.
     */
    public function __construct(ConsoleIo $io = null)
    {
        parent::__construct($io);
        $this->AclExtras = new AclExtras();
    }

    /**
     * Start up And load Acl Component / Aco model
     *
     * @return void
     */
    public function startup()
    {
        parent::startup();
        $this->AclExtras->startup();
        $this->AclExtras->setShell($this);

        if ($this->command) {
            try {
                TableRegistry::getTableLocator()->get('Aros')->getSchema();
            } catch (Exception $e) {
                $this->out(__d('cake_acl', 'Acl database tables not found. To create them, run:'));
                $this->out();
                $this->out('  bin/cake Migrations.migrations migrate -p Acl');
                $this->out();
                $this->_stop();
            }
        }
    }

    /**
     * Sync the ACO table
     *
     * @return void
     */
    public function acoSync()
    {
        $this->AclExtras->acoSync($this->params);
    }

    /**
     * Updates the Aco Tree with new controller actions.
     *
     * @return void
     */
    public function acoUpdate()
    {
        $this->AclExtras->acoUpdate($this->params);
    }

    /**
     * Get the option parser for this shell.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $plugin = [
            'short' => 'p',
            'help' => __('Plugin to process'),
        ];
        $parser->setDescription(__("Better manage, and easily synchronize you application's ACO tree"))
            ->addSubcommand('aco_update', [
                'parser' => [
                    'options' => compact('plugin'),
                ],
                'help' => __('Add new ACOs for new controllers and actions. Does not remove nodes from the ACO table.')
            ])->addSubcommand('aco_sync', [
                'parser' => [
                    'options' => compact('plugin'),
                ],
                'help' => __('Perform a full sync on the ACO table.' .
                    'Will create new ACOs or missing controllers and actions.' .
                    'Will also remove orphaned entries that no longer have a matching controller/action')
            ])->addSubcommand('recover', [
                'help' => __('Recover a corrupted Tree'),
                'parser' => [
                    'arguments' => [
                        'type' => [
                            'required' => true,
                            'help' => __('The type of tree to recover'),
                            'choices' => ['aco', 'aro']
                        ]
                    ]
                ]
            ]);

        return $parser;
    }

    /**
     * Recover an Acl Tree
     *
     * @return void
     */
    public function recover()
    {
        $this->AclExtras->args = $this->args;
        $this->AclExtras->recover();
    }
}
