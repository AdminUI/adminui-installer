<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use AdminUI\AdminUIInstaller\Controllers\BaseInstallController;

/**
 * For DEVELOPMENT purpose only
 */
class UninstallController extends BaseInstallController
{

    public function index()
    {
        $debug = config('app.debug') === true;
        if ($debug === true) {
            die("Not supported in production mode");
        }

        $installDirectory = 'adminui';
        $packages = Storage::build([
            'driver' => 'local',
            'root' => base_path('packages'),
        ]);

        $appDir = Storage::build([
            'driver' => 'local',
            'root' => base_path('app'),
        ]);

        if ($packages->exists($installDirectory)) {
            $packages->deleteDirectory($installDirectory);
        }

        if ($appDir->exists('Http/Controllers/Admin')) {
            $appDir->deleteDirectory('Http/Controllers/Admin');
        }
        if ($appDir->exists('Http/Controllers/Api')) {
            $appDir->deleteDirectory('Http/Controllers/Api');
        }
        if ($appDir->exists('Http/Controllers/Site')) {
            $appDir->deleteDirectory('Http/Controllers/Site');
        }
        if ($appDir->exists('Resources')) {
            $appDir->deleteDirectory('Resources');
        }
        if ($appDir->exists('Helpers/LiveProduct.php')) {
            $appDir->delete('Helpers/LiveProduct.php');
        }
        if ($appDir->exists('Models/AdminUICore.php')) {
            $appDir->delete('Models/AdminUICore.php');
        }
        if ($appDir->exists('Http/Middleware/HandleInertiaRequests.php')) {
            $appDir->delete('Http/Middleware/HandleInertiaRequests.php');
        }

        $this->runCommand(["rm,", ".env"]);
        $this->runCommand(["cp", ".env-clean", ".env"]);
        $this->runCommand(["rm", "composer.json"]);
        $this->runCommand(["cp", "composer-clean.json", "composer.json"]);

        $this->runComposerUpdate();

        $this->cleanDatabase();

        return response()->json([
            'status'    => 'success',
            'message'   => 'Uninstall complete'
        ]);
    }

    public function cleanDatabase()
    {
        $tables = [
            "activity_logs",
            "admin_contacts",
            "admin_contracts",
            "admins",
            "blog_blog_category",
            "blog_blog_tag",
            "blog_categories",
            "blog_comments",
            "blog_tags",
            "brand_supplier",
            "brands",
            "cart_lines",
            "carts",
            "categories",
            "category_product",
            "configurations",
            "countries",
            "dashboards",
            "document_product",
            "email_layouts",
            "email_variables",
            "google_categories",
            "google_category_product",
            "messages",
            "message_media",
            "media_folders",
            "media",
            "meta_schemas",
            "navigations",
            "notifications",
            "order_histories",
            "order_items",
            "order_statuses",
            "orders",
            "pages",
            "payments",
            "postage_rates",
            "postage_types",
            "postage_zones",
            "product_descriptions",
            "product_reviews",
            "product_statuses",
            "product_tag",
            "redirects",
            "sent_emails",
            "seos",
            "setups",
            "states",
            "tax_rates",
            "templates",
            "widgets",
            "wishlists"
        ];
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function runCommand(array $cmd)
    {
        $process = new Process($cmd);
        $process->setWorkingDirectory(base_path());
        $process->run();
    }
}
