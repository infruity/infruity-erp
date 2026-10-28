<div data-role-modal-fragment data-role-delete-url="{{ route('roles.destroy', $role->id_role) }}">
    @include('admin.role.show')
    @include('admin.role.edit')
</div>
