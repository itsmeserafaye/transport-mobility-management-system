<?php
// TPRS Dynamic UI Helper - Generates permission-based UI components

require_once __DIR__ . '/tprs_access_control.php';

// Generate dynamic sidebar navigation based on user permissions
function generateTPRSSidebar($current_module = '', $current_submodule = '') {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'tprs') {
        return '';
    }
    
    $sidebar_html = '';
    
    // Define module structure with submodules
    $modules = [
        'puv_database' => [
            'title' => 'PUV Database',
            'icon' => 'database',
            'submodules' => [
                'vehicle_and_operator_records' => 'Vehicle & Operator Records',
                'compliance_status_management' => 'Compliance Status',
                'violation_history_integration' => 'Violation History'
            ]
        ],
        'franchise_management' => [
            'title' => 'Franchise Management',
            'icon' => 'file-text',
            'submodules' => [
                'document_repository' => 'Document Repository',
                'franchise_application_workflow' => 'Application Workflow',
                'franchise_lifecycle_management' => 'Lifecycle Management',
                'route_and_schedule_publication' => 'Route & Schedule'
            ]
        ],
        'vehicle_inspection' => [
            'title' => 'Vehicle Inspection',
            'icon' => 'search',
            'submodules' => [
                'inspection_scheduling' => 'Inspection Scheduling',
                'inspection_result_recording' => 'Result Recording',
                'inspection_history_tracking' => 'History Tracking'
            ]
        ],
        'traffic_violation_ticketing' => [
            'title' => 'Traffic Violations',
            'icon' => 'alert-triangle',
            'submodules' => [
                'violation_record_management' => 'Violation Records'
            ]
        ],
        'terminal_management' => [
            'title' => 'Terminal Management',
            'icon' => 'map-pin',
            'submodules' => [
                'terminal_assignment_management' => 'Terminal Assignment',
                'roster_and_delivery' => 'Roster & Directory',
                'public_transparency' => 'Public Transparency'
            ]
        ]
    ];
    
    // Dashboard link (always accessible)
    $sidebar_html .= '<a href="../../index.php" class="w-full flex items-center p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">';
    $sidebar_html .= '<i data-lucide="home" class="w-5 h-5 mr-3"></i>';
    $sidebar_html .= '<span class="text-sm font-medium">Dashboard</span>';
    $sidebar_html .= '</a>';
    
    // Generate module navigation
    foreach ($modules as $module_key => $module_data) {
        $access_level = getTPRSAccessLevel($module_key);
        
        // Skip modules with no access
        if ($access_level === 'none') {
            continue;
        }
        
        $is_active = ($current_module === $module_key);
        $active_class = $is_active ? 'text-orange-600 bg-orange-50' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700';
        
        $sidebar_html .= '<div class="space-y-1">';
        $sidebar_html .= '<button onclick="toggleDropdown(\'' . $module_key . '\')" class="w-full flex items-center justify-between p-2 rounded-xl ' . $active_class . ' transition-all">';
        $sidebar_html .= '<div class="flex items-center">';
        $sidebar_html .= '<i data-lucide="' . $module_data['icon'] . '" class="w-5 h-5 mr-3"></i>';
        $sidebar_html .= '<span class="text-sm font-medium">' . $module_data['title'] . '</span>';
        
        // Add access level indicator
        $access_badge = '';
        switch ($access_level) {
            case 'read_only':
                $access_badge = '<span class="ml-2 px-1.5 py-0.5 text-xs bg-blue-100 text-blue-800 rounded">Read</span>';
                break;
            case 'read_write':
                $access_badge = '<span class="ml-2 px-1.5 py-0.5 text-xs bg-green-100 text-green-800 rounded">Edit</span>';
                break;
            case 'full':
                $access_badge = '<span class="ml-2 px-1.5 py-0.5 text-xs bg-purple-100 text-purple-800 rounded">Full</span>';
                break;
        }
        $sidebar_html .= $access_badge;
        
        $sidebar_html .= '</div>';
        $sidebar_html .= '<i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="' . $module_key . '-icon"></i>';
        $sidebar_html .= '</button>';
        
        // Generate submodule menu
        $menu_class = $is_active ? '' : 'hidden';
        $sidebar_html .= '<div id="' . $module_key . '-menu" class="' . $menu_class . ' ml-8 space-y-1">';
        
        foreach ($module_data['submodules'] as $submodule_key => $submodule_title) {
            // Check if user has access to this submodule
            if (hasTPRSPermission($module_key, 'read')) {
                $is_current = ($current_submodule === $submodule_key);
                $submodule_class = $is_current ? 'bg-orange-100 text-orange-800' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700';
                
                $sidebar_html .= '<a href="../../' . $module_key . '/' . $submodule_key . '/" class="block p-2 text-sm ' . $submodule_class . ' rounded-lg">';
                $sidebar_html .= $submodule_title;
                $sidebar_html .= '</a>';
            }
        }
        
        $sidebar_html .= '</div>';
        $sidebar_html .= '</div>';
    }
    
    return $sidebar_html;
}

// Generate action buttons based on user permissions
function generateTPRSActionButtons($module, $buttons) {
    $html = '';
    
    foreach ($buttons as $permission => $config) {
        if (hasTPRSPermission($module, $permission)) {
            if (isset($config['dropdown'])) {
                // Handle dropdown button
                $html .= '<div class="relative">';
                $html .= '<button onclick="' . $config['onclick'] . '" class="flex items-center px-4 py-2 ' . $config['class'] . ' text-white rounded-lg transition-colors">';
                $html .= '<i data-lucide="' . $config['icon'] . '" class="w-4 h-4 mr-2"></i>';
                $html .= $config['label'];
                $html .= '<i data-lucide="chevron-down" class="w-4 h-4 ml-2"></i>';
                $html .= '</button>';
                
                // Generate dropdown menu
                $html .= '<div id="' . $config['dropdown']['id'] . '" class="hidden absolute top-full left-0 mt-2 w-48 bg-white border border-slate-200 rounded-lg shadow-lg z-10">';
                foreach ($config['dropdown']['items'] as $item) {
                    $html .= '<a href="' . $item['href'] . '" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">' . $item['label'] . '</a>';
                }
                $html .= '</div>';
                $html .= '</div>';
            } else {
                // Handle regular button
                $html .= '<button onclick="' . $config['onclick'] . '" class="flex items-center px-4 py-2 ' . $config['class'] . ' text-white rounded-lg transition-colors">';
                $html .= '<i data-lucide="' . $config['icon'] . '" class="w-4 h-4 mr-2"></i>';
                $html .= $config['label'];
                $html .= '</button>';
            }
        }
    }
    
    return $html;
}

// Generate table action buttons for individual records
function generateTPRSRowActions($module, $actions) {
    $html = '';
    
    foreach ($actions as $permission => $config) {
        if (hasTPRSPermission($module, $permission)) {
            $html .= '<button onclick="' . $config['onclick'] . '" class="p-1 ' . $config['class'] . ' rounded" title="' . $config['title'] . '">';
            $html .= '<i data-lucide="' . $config['icon'] . '" class="w-4 h-4"></i>';
            $html .= '</button>';
        }
    }
    
    return $html;
}

// Get access level description for UI display
function getAccessLevelDescription($module) {
    $access_level = getTPRSAccessLevel($module);
    
    switch ($access_level) {
        case 'read_only':
            return 'You have read-only access to this module.';
        case 'read_write':
            return 'You can view and edit records in this module.';
        case 'full':
            return 'You have full access to all features in this module.';
        case 'none':
        default:
            return 'You do not have access to this module.';
    }
}
?>