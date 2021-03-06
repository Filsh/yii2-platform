<?php

namespace yii\platform\console;

class Application extends \yii\console\Application
{
    /**
     * The option name for specifying the application project host.
     */
    const OPTION_APPHOST = 'apphost';
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'application' => [
                'class' => 'yii\platform\behaviors\Application'
            ]
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'urlManager' => ['class' => 'yii\platform\web\UrlManager'],
            'request' => ['class' => 'yii\platform\console\Request'],
            'i18n' => ['class' => 'yii\platform\i18n\I18N'],
            'runner' => ['class' => 'yii\platform\runners\Runner'],
        ]);
    }
    
    /**
     * @inheritdoc
     */
    public function coreCommands()
    {
        return array_merge(parent::coreCommands(), [
            'migrate' => 'yii\platform\console\controllers\MigrateController',
            'runner' => 'yii\platform\console\controllers\RunnerController',
            'message' => 'yii\platform\console\controllers\MessageController',
            'platform' => 'yii\platform\console\controllers\PlatformController',
        ]);
    }
    
    /**
     * Returns the configuration of the built-in runners.
     * @return array the configuration of the built-in runners.
     */
    public function coreRunners()
    {
        return [
            'locations' => 'yii\platform\runners\LocationsRunner',
            'regions' => 'yii\platform\runners\RegionsRunner',
            'timezones' => 'yii\platform\runners\TimezonesRunner',
            'countries' => 'yii\platform\runners\CountriesRunner',
            'message-countries' => 'yii\platform\runners\MessageCountriesRunner',
        ];
    }
}