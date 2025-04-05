<tr class="user-item">
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
        #{{id}}
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="flex items-center">
            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-700">
                {{initials}}
            </div>
            <div class="ml-4">
                <div class="text-sm font-medium text-gray-900">{{name}} {{surname}}</div>
                <div class="text-sm text-gray-500">{{email}}</div>
            </div>
        </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap" id="user-role-{{id}}">
        <div class="inline-flex items-center">
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{roleColorClass}}">
                {{roleLabel}}
            </span>
            <div class="relative ml-2" x-data="{ roleDropdownOpen: false }">
                <button @click="roleDropdownOpen = !roleDropdownOpen" 
                        type="button" 
                        class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-edit text-xs"></i>
                </button>
                <div x-show="roleDropdownOpen" 
                     @click.away="roleDropdownOpen = false"
                     class="origin-top-right absolute right-0 mt-2 w-36 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-20">
                    <div class="py-1">
                        <a href="#" @click.prevent="updateUserRole({{id}}, 'user'); roleDropdownOpen = false" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                           Użytkownik
                        </a>
                        <a href="#" @click.prevent="updateUserRole({{id}}, 'manager'); roleDropdownOpen = false" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                           Menedżer
                        </a>
                        <a href="#" @click.prevent="updateUserRole({{id}}, 'admin'); roleDropdownOpen = false" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                           Administrator
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap" id="user-status-{{id}}">
        <div class="inline-flex items-center">
            <button @click="toggleUserStatus({{id}}, {{active}})" 
                    class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 {{statusBgClass}}">
                <span class="{{active ? 'translate-x-5' : 'translate-x-0'}} inline-block h-5 w-5 rounded-full bg-white shadow transform transition ease-in-out duration-200"></span>
            </button>
            <span class="ml-2 text-sm {{statusTextClass}}">{{statusLabel}}</span>
        </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
        {{created_at}}
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
        <button @click="openEditModal({{id}})" class="text-blue-600 hover:text-blue-900 mr-3">
            <i class="fas fa-edit"></i> Edytuj
        </button>
        <button @click="openDeleteModal({{id}}, '{{name}}', '{{surname}}', '{{email}}')" class="text-red-600 hover:text-red-900">
            <i class="fas fa-trash-alt"></i> Usuń
        </button>
    </td>
</tr>
