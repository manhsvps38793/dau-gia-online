<?php
namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class LogHelper
{
    /**
     * Ghi log CRUD
     *
     * @param string $table_name
     * @param string $action_type (INSERT, UPDATE, DELETE)
     * @param array|null $old_value
     * @param array|null $new_value
     * @param string|null $description
     */
    public static function log(string $table_name, string $action_type, $old_value = null, $new_value = null, $description = null)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'table_name' => $table_name,
            'action_type' => $action_type,
            'old_value' => $old_value ? json_encode($old_value) : null,
            'new_value' => $new_value ? json_encode($new_value) : null,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
        ]);
    }
}
