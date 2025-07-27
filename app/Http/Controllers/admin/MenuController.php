<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * This controller handles everything related to menus in the admin area.
 */
class MenuController extends Controller
{
    /**
     * Shows a page with a list of all menus.
     *
     * Gets all menus from the database and loads a page to display them.
     */
    public function index()
    {
        $menus = Menu::all();
        return view('admin.menu', compact('menus'));
    }

    /**
     * Shows a form to create a new menu.
     *
     * Not used right now.
     */
    public function create()
    {
        //
    }

    /**
     * Saves a new menu to the database.
     *
     * Checks if the input is correct, then creates a new menu with details
     * like name, icon, and link. It also makes a simple version of the menu name
     * (like "Main Menu" becomes "main-menu").
     */
    public function store(StoreMenuRequest $request)
    {
        $validated = $request->validated();
        $newOrder = isset($validated['order']) ? (int) $validated['order'] : (Menu::where('parent_id', $validated['parent_id'] ?? null)->max('order') ?? 0) + 1;
        $parentId = $validated['parent_id'] ? (int) $validated['parent_id'] : null;
        Menu::where('parent_id', $parentId)
            ->where('order', '>=', $newOrder)
            ->increment('order');
        $menu = Menu::create([
            'title' => $validated['menu_name'],
            'data_key' => Str::kebab($validated['menu_name']),
            'parent_id' => $parentId,
            'icon' => $validated['icon'],
            'order' => $newOrder,
            'url' => $validated['url'],
            'permission_name' => $validated['permission'],
            'status' => $validated['is_active'],
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return $this->jsonSuccess($menu, 'Menu created successfully.');
    }

    /**
     * Shows details of a specific menu.
     *
     * Not used right now.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Gets a menu to edit it.
     *
     * Finds a menu by its ID and sends it back to show in an edit form.
     */
    public function edit(string $id)
    {
        $menu = Menu::findOrFail($id);
        return $this->jsonSuccess($menu, 'Menu fetched successfully.');
    }

    /**
     * Updates a menu in the database.
     *
     * Checks if the new details are correct, then updates the menu
     * with new information like name, icon, or link.
     */
    public function update(UpdateMenuRequest $request, Menu $menu)
    {
        $validated = $request->validated();
        $newOrder = (int) $validated['order'];
        $oldOrder = $menu->order ?? 0;
        $parentId = $validated['parent_id'] ? (int) $validated['parent_id'] : null;
        if ($newOrder !== $oldOrder || $parentId !== $menu->parent_id) {
            $this->reorderMenuItems($menu, $newOrder, $oldOrder, $parentId);
        }

        $menu->update([
            'title' => $validated['menu_name'],
            'parent_id' => $parentId,
            'icon' => $validated['icon'],
            'order' => $newOrder,
            'url' => $validated['url'],
            'permission_name' => $validated['permission'],
            'status' => $validated['is_active'],
            'updated_by' => auth()->id(),
        ]);

        return $this->jsonSuccess($menu, 'Menu updated successfully.');
    }
    /**
     * Deletes a menu from the database.
     *
     * Finds a menu by its ID and removes it.
     */
    public function destroy(string $id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();
        return $this->jsonSuccess($menu, 'Menu deleted successfully.');
    }

    /**
     * Gets a list of all active menus.
     *
     * Grabs only the active menus and sends them back as a list
     * for things like showing a navigation menu on a webpage.
     */
    public function menuList()
    {
        $query = Menu::select('id', 'title', 'url', 'icon', 'parent_id', 'order', 'data_key', 'status', 'created_at', 'updated_at')->where('status', 1);
        $data = $query->get();
        return response()->json($data);
    }

    protected function reorderMenuItems(Menu $currentMenu, int $newOrder, int $oldOrder, ?int $parentId): void
    {
        if ($currentMenu->parent_id !== $parentId) {
            if ($oldOrder > 0) {
                Menu::where('parent_id', $currentMenu->parent_id)
                    ->where('order', '>', $oldOrder)
                    ->decrement('order');
            }

            Menu::where('parent_id', $parentId)
                ->where('order', '>=', $newOrder)
                ->increment('order');
        } elseif ($oldOrder > 0) {
            if ($newOrder < $oldOrder) {
                Menu::where('parent_id', $parentId)
                    ->where('order', '>=', $newOrder)
                    ->where('order', '<', $oldOrder)
                    ->increment('order');
            } elseif ($newOrder > $oldOrder) {
                Menu::where('parent_id', $parentId)
                    ->where('order', '>', $oldOrder)
                    ->where('order', '<=', $newOrder)
                    ->decrement('order');
            }
        } else {
            Menu::where('parent_id', $parentId)
                ->where('order', '>=', $newOrder)
                ->increment('order');
        }
    }

    public function getLogs(Menu $menu)
    {
        $logs = $menu->activities()
            ->with('causer')
            ->latest()
            ->get()
            ->map(function ($log) {
                return [
                    'event' => $log->event,
                    'description' => $log->description,
                    'properties' => $log->properties->toArray(),
                    'causer' => $log->causer ? ['name' => $log->causer->name] : null,
                    'created_at' => $log->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => $logs, // DataTables expects 'data' key, but we'll use 'logs' as per your code
            'title' => $menu->title // Add the menu title to the response
        ]);
    }
}
