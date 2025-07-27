<?php
namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CoreCompany;
use App\Models\CompanyDbConfig;
use App\Models\ThemeCustomizer;
use App\Models\GeneralSettings;
use App\Http\Requests\SaveConfigRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings');
    }
    public function company()
    {
        $company = CoreCompany::all();
        return $this->jsonSuccess($company, 'Company fetched successfully.');
    }
    public function saveCompanyConfig(SaveConfigRequest $request)
    {
        $validated = $request->validated();
        $status = !empty($validated['is_active']) ? 1 : 0;
        $data = [
            'db_connection' => $validated['db_connection'],
            'db_host' => $validated['db_host'],
            'db_port' => $validated['db_port'],
            'db_database' => $validated['db_database'],
            'db_username' => $validated['db_username'],
            'db_password' => $validated['db_password'],
            'status' => $status,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ];
        $existingConfig = CompanyDbConfig::where('company_id', $validated['company_id'])
            ->where('db_name', $validated['db_name'])
            ->first();
        if ($existingConfig) {
            $existingConfig->update($data);
            return $this->jsonSuccess($existingConfig, 'Configuration updated successfully.');
        } else {
            $data['company_id'] = $validated['company_id'];
            $data['db_name'] = $validated['db_name'];
            $newConfig = CompanyDbConfig::create($data);
            return $this->jsonSuccess($newConfig, 'Configuration created successfully.');
        }
    }
    public function getCompanyConfig(Request $request, string $id)
    {
        $configs = CompanyDbConfig::where('company_id', $id)->get();
        $data = ['hrims' => null, 'expense' => null];
        foreach ($configs as $config) {
            if ($config->db_name === 'hrims') {
                $data['hrims'] = $config;
            } elseif ($config->db_name === 'expense') {
                $data['expense'] = $config;
            }
        }
        return $this->jsonSuccess($data, 'Company details fetched successfully.');
    }
    public function saveThemeSettings(Request $request)
    {
        $user = Auth::user();
        $data = $request->all();
        $validated = $request->validate([
            'layout' => 'in:vertical,horizontal,twocolumn,semibox',
            'sidebar_user_profile' => 'boolean',
            'theme' => 'in:default,saas,corporate,galaxy,material,creative,minimal,modern,interactive,classic,vintage',
            'color_scheme' => 'in:light,dark',
            'sidebar_visibility' => 'in:show,hidden',
            'layout_width' => 'in:fluid,boxed',
            'layout_position' => 'in:fixed,scrollable',
            'topbar_color' => 'in:light,dark',
            'sidebar_size' => 'in:lg,md,sm,sm-hover',
            'sidebar_view' => 'in:default,detached',
            'sidebar_color' => 'in:light,dark,gradient,gradient-2,gradient-3,gradient-4',
            'sidebar_image' => 'in:none,img-1,img-2,img-3,img-4',
            'primary_color' => 'in:default,green,purple,blue',
            'preloader' => 'in:enable,disable',
            'body_image' => 'in:none,img-1,img-2,img-3',
        ]);
        ThemeCustomizer::updateOrCreate(['user_id' => $user ? $user->id : null], $validated);
        return response()->json(['message' => 'Settings saved successfully']);
    }
    public function saveGeneralSettings(Request $request)
    {
        $request->validate([
            'projectName' => 'required|string|max:255',
            'timeZone' => 'required|string|max:255',
            'language' => 'required|string|max:255',
            'siteUrl' => 'nullable|url|max:255',
            'contactInfo' => 'nullable|string|max:500',
            'siteDescription' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|max:2048',
            'maintenanceMode' => 'nullable|boolean',
        ]);

        $data = [
            'project_name' => $request->projectName,
            'time_zone' => $request->timeZone,
            'default_language' => $request->language,
            'maintenance_mode' => $request->maintenanceMode ? 1 : 0,
            'site_url' => $request->siteUrl,
            'contact_info' => $request->contactInfo,
            'site_description' => $request->siteDescription,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ];

        if ($request->hasFile('logo')) {
            $timestamp = now()->format('YmdHis');
            $folder = "uploads/logos/{$timestamp}";
            $fileName = Str::random(10) . '.' . $request->file('logo')->getClientOriginalExtension();
            $path = $request->file('logo')->storeAs($folder, $fileName, 'public');
            $data['logo_path'] = $path;
        }

        $existingSettings = GeneralSettings::first();
        if ($existingSettings) {
            $existingSettings->update($data);
        } else {
            $data['created_by'] = auth()->id();
            $data['created_at'] = now();
            GeneralSettings::create($data);
        }
         return $this->jsonSuccess($data, 'Settings saved successfully.');
    }
    public function getGeneralSettings()
    {
        $settings = GeneralSettings::first();
        if (!$settings) {
            return response()->json(['message' => 'No settings found'], 404);
        }
        return $this->jsonSuccess($settings, 'Settings fetched successfully.');
    }

}
