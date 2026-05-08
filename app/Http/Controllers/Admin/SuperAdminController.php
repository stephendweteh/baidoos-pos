<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DayClosing;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:superadmin']);
    }

    /**
     * Show the system settings page.
     */
    public function settings()
    {
        return view('admin.settings');
    }

    /**
     * Update .env configuration values for SMS and SMTP.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'ARKESEL_API_KEY'             => 'nullable|string|max:200',
            'ARKESEL_SENDER_ID'           => 'nullable|string|max:50',
            'MAIL_MAILER'                 => 'nullable|string|max:50',
            'MAIL_HOST'                   => 'nullable|string|max:200',
            'MAIL_PORT'                   => 'nullable|integer',
            'MAIL_USERNAME'               => 'nullable|string|max:200',
            'MAIL_PASSWORD'               => 'nullable|string|max:200',
            'MAIL_ENCRYPTION'             => 'nullable|string|max:10',
            'MAIL_FROM_ADDRESS'           => 'nullable|email|max:200',
            'MAIL_FROM_NAME'              => 'nullable|string|max:100',
            'MTN_MOMO_BASE_URL'           => 'nullable|string|max:200',
            'MTN_MOMO_SUBSCRIPTION_KEY'   => 'nullable|string|max:200',
            'MTN_MOMO_API_USER'           => 'nullable|string|max:200',
            'MTN_MOMO_API_KEY'            => 'nullable|string|max:200',
            'MTN_MOMO_TARGET_ENVIRONMENT' => 'nullable|string|max:50',
            'MTN_MOMO_CURRENCY'           => 'nullable|string|max:5',
            'MTN_MOMO_CALLBACK_URL'       => 'nullable|string|max:300',
            'MTN_MOMO_MERCHANT_NAME'      => 'nullable|string|max:100',
        ]);

        $keys = [
            'ARKESEL_API_KEY', 'ARKESEL_SENDER_ID',
            'MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT',
            'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
            'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME',
            'MTN_MOMO_BASE_URL', 'MTN_MOMO_SUBSCRIPTION_KEY',
            'MTN_MOMO_API_USER', 'MTN_MOMO_API_KEY',
            'MTN_MOMO_TARGET_ENVIRONMENT', 'MTN_MOMO_CURRENCY',
            'MTN_MOMO_CALLBACK_URL', 'MTN_MOMO_MERCHANT_NAME',
        ];

        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        foreach ($keys as $key) {
            $value = $request->input($key, '');
            // Wrap value in quotes if it contains spaces
            $escaped = str_contains($value, ' ') ? '"' . $value . '"' : $value;

            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $envContent);
            } else {
                $envContent .= "\n{$key}={$escaped}";
            }
        }

        file_put_contents($envPath, $envContent);

        // Clear config cache to pick up new .env values on next request
        try {
            $bootstrapCache = base_path('bootstrap/cache/config.php');
            if (file_exists($bootstrapCache)) {
                @unlink($bootstrapCache);
            }
        } catch (\Throwable $e) {
            // Non-fatal
        }

        // Flush cached MTN MoMo access token so new credentials take effect immediately
        \Illuminate\Support\Facades\Cache::forget('mtn_momo_access_token');

        return back()->with('success', 'Settings saved successfully.');
    }

    /**
     * Reset (delete) all sales, sale items, and day closings.
     */
    public function resetSales(Request $request)
    {
        $request->validate([
            'confirm' => 'required|in:RESET',
        ]);

        DB::transaction(function () {
            SaleItem::query()->delete();
            Sale::query()->delete();
            DayClosing::query()->delete();
        });

        return redirect()->route('dashboard')
            ->with('success', 'All sales, sale items, and day closings have been permanently deleted.');
    }
}

