<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeBaseJsonRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:bj-request {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a FormRequest inheriting from BaseJsonFormRequest';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $className = preg_replace('/[^A-Za-z0-9_]/', '', $name);
        $path = app_path("Http/Requests/{$className}.php");

        if (file_exists($path)) {
            $this->components->error("Request {$className}.php already exist!");
            return 1;
        }

        $stub = <<<EOT
<?php

namespace App\\Http\\Requests;

use App\\Http\\Requests\\BaseJsonFormRequest;
use Illuminate\\Contracts\\Validation\\ValidationRule;

class {$className} extends BaseJsonFormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [
            //
        ];
    }
}
EOT;

        file_put_contents($path, $stub);
        $this->components->info("Request [app/Http/Requests/{$className}.php] created successfully.");
        return 0;
    }
}
