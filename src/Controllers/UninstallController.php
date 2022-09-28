<?php

namespace AdminUI\AdminUIInstaller\Controllers;

use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use AdminUI\AdminUIInstaller\Controllers\BaseInstallController;
use AdminUI\AdminUIInstaller\Services\ApplicationService;

/**
 * For DEVELOPMENT purpose only
 */
class UninstallController extends BaseInstallController
{

    public function __construct(
        protected ApplicationService $appService,
    ) {
    }

    public function index()
    {
        $debug = config('app.debug') === true;
        if ($debug === false) {
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

        $baseDir = Storage::build([
            'driver' => 'local',
            'root' => base_path()
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
        if ($appDir->exists('Hooks')) {
            $appDir->deleteDirectory('Hooks');
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

        $configs = ['adminui', 'adminuiStaff', 'google2fa', 'permission'];

        foreach ($configs as $config) {
            if ($baseDir->exists('config/' . $config . '.php')) {
                $baseDir->delete('config/' . $config . '.php');
            }
        }

        if ($baseDir->exists('.env-clean')) {
            $this->runCommand(["rm,", ".env"]);
            $this->runCommand(["cp", ".env-clean", ".env"]);
        }
        if ($baseDir->exists('composer-clean.json')) {
            $this->runCommand(["rm", "composer.json"]);
            $this->runCommand(["cp", "composer-clean.json", "composer.json"]);
        }


        $this->cleanDatabase();
        $this->appService->composerUpdate();

        return response()->json([
            'status'    => 'success',
            'message'   => 'Uninstall complete'
        ]);
    }

    public function cleanDatabase()
    {
        $tables = [
            "activity_logs",
            "account_user",
            "accounts",
            "addresses",
            "admin_contacts",
            "admin_contracts",
            "admin_subscriptions",
            "admins",
            "back_orders",
            "blog_blog_category",
            "blog_blog_tag",
            "blog_categories",
            "blog_comments",
            "blog_tags",
            "blogs",
            "brand_supplier",
            "brands",
            "cart_lines",
            "carts",
            "categories",
            "category_product",
            "communications",
            "configuration_sections",
            "configurations",
            "consent_categories",
            "consent_cookies",
            "consent_snippets",
            "countries",
            "dashboards",
            "document_product",
            "email_layouts",
            "email_variables",
            "form_fields",
            "form_submissions",
            "forms",
            "google_categories",
            "google_category_product",
            "import_processes",
            "imports",
            "invites",
            "issue_admin_groups",
            "issue_assignment_groups",
            "issue_assignments",
            "issue_message_statuses",
            "issue_messages",
            "issue_statuses",
            "issues",
            "media_product",
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
            "page_layouts",
            "pages",
            "payment_provider_items",
            "payment_providers",
            "payments",
            "postage_rates",
            "postage_sizes",
            "postage_zone_country",
            "postage_types",
            "postage_zones",
            "product_addons",
            "product_alternative",
            "product_descriptions",
            "product_logos",
            "product_product_logos",
            "product_recommended",
            "product_reviews",
            "product_section",
            "product_statuses",
            "product_types",
            "product_variant",
            "product_views",
            "product_tag",
            "products",
            "project_task_group",
            "projects",
            "promotions",
            "purchase_order_items",
            "purchase_orders",
            "redirects",
            "sections",
            "sent_emails",
            "seos",
            "setups",
            "slider_items",
            "sliders",
            "states",
            "stock_adjustments",
            "stock_histories",
            "sub_task_admins",
            "sub_tasks",
            "subscribers",
            "suppliers",
            "tag_sections",
            "tags",
            "task_groups",
            "task_histories",
            "task_items",
            "task_notes",
            "task_statuses",
            "tasks",
            "tax_rates",
            "templates",
            "top_sellers",
            "user_searches",
            "variants",
            "widgets",
            "wishlists"
        ];
        foreach ($tables as $table) {
            Schema::disableForeignKeyConstraints();
            Schema::dropIfExists($table);
            Schema::enableForeignKeyConstraints();
        }
    }

    public function runCommand(array $cmd)
    {
        $process = new Process($cmd);
        $process->setWorkingDirectory(base_path());
        $process->run();
    }
}
