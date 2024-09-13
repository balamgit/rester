<?php

namespace Itsmg\Rester\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ResterCli extends Command
{
    protected $signature = 'rester:create  {--group= : The API group} {--api-name= : The API class name}';

    protected $description = 'Generate a new rester api group class from a template';

    protected $help;

    public function __construct()
    {
        parent::__construct();
        $this->help = $this->helpTemplate();
    }

    public function handle()
    {
        $groupName = $this->option('group');
        $className = $this->option('api-name');

        if (empty($groupName) || empty($className)) {
            $this->info($this->help);
            return;
        }

        $groupClassName = $groupName.'Base';
        $folderPath = app_path('Rester/'.$groupName);

        if (!File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0755, true);
            $this->info("Created directory: {$folderPath}");
        } else {
            $this->info("Directory already exists: {$folderPath}");
        }

        $this->defineFilePath($folderPath, $className, 'getTemplate', $groupName);
        $this->defineFilePath($folderPath, $groupClassName, 'getTemplateBase', $groupName);
    }

    private function helpTemplate(): string
    {
        return <<<EOT
Generate a new API class inside a specific group folder.

Usage:
    php artisan rester:create --group=<group> --api-name=<class>

Arguments:
    --group     The folder group under app/Rester where the API base class should be created.
    --api-name  The name of the API class file to be generated inside app/Rester.

Examples:
    php artisan rester:create --group=ApiGroup --api-name=MyApiClass
        - This will create following files 
           app/Rester/ApiGroup/MyApiBase.php.
           app/Rester/ApiGroup/MyApiClass.php.

If the group folder doesn't exist, it will be created automatically.
EOT;
    }

    private function getTemplate($className, $group): string
    {
        $groupClassName = $group.'Base';

        $namespace = "App\Rester\\".$group;
        return <<<EOT
        <?php

        namespace {$namespace};

        class {$className} extends {$groupClassName}
        {
        }
        EOT;
    }

    private function getTemplateBase($className, $group): string
    {
        $namespace = "App\Rester\\".$group;
        return <<<EOT
        <?php

        namespace {$namespace};

        use Itsmg\Rester\Rester;
        use Itsmg\Rester\Contracts\WithDefaultBaseUri;

        class {$className} extends Rester implements WithDefaultBaseUri
        {
            public function defaultBaseUri(): string
            {
                // TODO: Implement defaultBaseUri() method.
            }
        }
        EOT;
    }

    /**
     * @param $folderPath
     * @param $className
     * @param $templateOperation
     * @param $groupName
     * @return void
     */
    public function defineFilePath($folderPath, $className, $templateOperation, $groupName): void
    {
        $filePath = $folderPath . "/{$className}.php";

        if (File::exists($filePath)) {
            return;
        }

        // Get the content of the template
        $template = $this->{$templateOperation}($className, $groupName);

        File::put($filePath, $template);
        $this->info("Class {$className} created successfully.");
    }
}
