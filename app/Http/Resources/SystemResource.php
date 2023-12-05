<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $api_key = $this->api_key === null || $this->api_key === 'NONE'? 'NONE': Crypt::decrypt($this->api_key);
        $key_status = $this->key_deactivated_at??"NONE";
        $status_message = ($this->status === 1? 'Active':$this->status === 2)? "Maintainance": "Server Down";
        $total_user = DB::table('systems as s')
            ->join('system_roles as sr', 'sr.system_id', '=', 's.id')
            ->join('position_system_roles as psr', 'psr.system_role_id', '=', 'sr.id')
            ->join('designations as d', 'd.id', '=', 'psr.designation_id')
            ->join('assigned_areas as aa', 'aa.designation_id', '=', 'd.id')
            ->where('s.id', '=', $this->id)
            ->select(DB::raw('count(aa.id) as users'))
            ->first();
        
        $system_roles = SystemRoleResource::collection($this->systemRoles);
        $total_permissions = DB::table('systems as s')
            ->join('system_roles as sr', 'sr.system_id', '=', 's.id')
            ->join('role_module_permissions as rmp', 'rmp.system_role_id', '=', 'sr.id')
            ->where('s.id', '=', $this->id)
            ->select(DB::raw('count(rmp.id) as permissions'))
            ->first();

        $date_created = $this->created_at;
        $date_modified = $this->updated_at;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'domain' => Crypt::decrypt($this->domain),
            'api_key' => $api_key,
            'key_status' => $key_status,
            'status' => $this->status,
            'status_message' => $status_message,
            'users' => $total_user->users,
            'permissions' => $total_permissions->permissions,
            'roles_assigned' => count($system_roles),
            'system_roles' => $system_roles,
            'date_created' => $date_created,
            'date_modified' => $date_modified
        ];
    }
}
