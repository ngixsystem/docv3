@extends('layouts.app')
@section('page-title', 'Отделы, группы и компании')

@section('topbar-actions')
  <button type="button" class="btn btn-secondary" onclick="openModal('createDepartmentModal')">+ Отдел</button>
  <button type="button" class="btn btn-secondary" onclick="openModal('createGroupModal')">+ Группа</button>
  <button type="button" class="btn btn-primary" onclick="openModal('createCompanyModal')">+ Компания</button>
@endsection

@section('content')
<div style="display:grid; grid-template-columns:1fr; gap:22px;">
  <div style="display:grid; grid-template-columns:1.1fr .9fr; gap:22px; align-items:start;">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Отделы</div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Название</th>
              <th>Руководитель</th>
              <th>Сотрудники</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            @foreach($departments as $department)
              <tr>
                <td>
                  <div style="font-weight:600;">{{ $department->name }}</div>
                  <div style="font-size:12px; color:var(--text-muted);">{{ $department->description ?: 'Без описания' }}</div>
                </td>
                <td>{{ $department->head?->name ?? 'Не назначен' }}</td>
                <td>{{ $department->users_count }}</td>
                <td>
                  <div style="display:flex; gap:6px; flex-wrap:wrap;">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="openModal('editDepartmentModal-{{ $department->id }}')">Редактировать</button>
                    <form method="POST" action="{{ route('departments.destroy', $department) }}" onsubmit="return confirm('Удалить отдел?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">Группы</div>
      </div>
      <div class="card-body" style="display:flex; flex-direction:column; gap:14px;">
        @foreach($groups as $group)
          <div style="border:1px solid var(--border); border-radius:12px; padding:14px;">
            <div style="display:flex; justify-content:space-between; gap:12px; margin-bottom:8px;">
              <div>
                <div style="font-weight:700;">{{ $group->name }}</div>
                <div style="font-size:12px; color:var(--text-muted);">{{ $group->description ?: 'Без описания' }}</div>
              </div>
              <span class="status-pill status-active">{{ $group->users_count }} участников</span>
            </div>
            <div style="font-size:12px; color:var(--text-muted); margin-bottom:12px;">
              {{ $group->users->pluck('name')->join(', ') ?: 'Участники не добавлены' }}
            </div>
            <div style="display:flex; gap:6px; flex-wrap:wrap;">
              <button type="button" class="btn btn-sm btn-secondary" onclick="openModal('editGroupModal-{{ $group->id }}')">Редактировать</button>
              <form method="POST" action="{{ route('groups.destroy', $group) }}" onsubmit="return confirm('Удалить группу?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">Компании-отправители</div>
    </div>
    <div class="card-body" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:14px;">
      @forelse($companies as $company)
        <div style="border:1px solid var(--border); border-radius:14px; padding:16px;">
          <div style="font-weight:700; margin-bottom:8px;">{{ $company->name }}</div>
          <div style="font-size:13px; color:var(--text-muted); line-height:1.7; min-height:66px;">
            {{ $company->details ?: 'Подробная информация не заполнена.' }}
          </div>
          <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:14px;">
            <button type="button" class="btn btn-sm btn-secondary" onclick="openModal('editCompanyModal-{{ $company->id }}')">Редактировать</button>
            <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('Удалить компанию?')">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
            </form>
          </div>
        </div>
      @empty
        <div style="color:var(--text-muted);">Компании пока не добавлены.</div>
      @endforelse
    </div>
  </div>
</div>

<div class="modal-overlay" id="createDepartmentModal" onclick="if(event.target===this)closeModal('createDepartmentModal')">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Новый отдел</div><button type="button" class="modal-close" onclick="closeModal('createDepartmentModal')">×</button></div>
    <form method="POST" action="{{ route('departments.store') }}">
      @csrf
      <div class="modal-body">
        @include('admin.organization.partials.department-form', ['departmentItem' => null, 'users' => $users])
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('createDepartmentModal')">Отмена</button>
        <button type="submit" class="btn btn-primary">Создать</button>
      </div>
    </form>
  </div>
</div>

@foreach($departments as $department)
  <div class="modal-overlay" id="editDepartmentModal-{{ $department->id }}" onclick="if(event.target===this)closeModal('editDepartmentModal-{{ $department->id }}')">
    <div class="modal">
      <div class="modal-header"><div class="modal-title">Редактирование отдела</div><button type="button" class="modal-close" onclick="closeModal('editDepartmentModal-{{ $department->id }}')">×</button></div>
      <form method="POST" action="{{ route('departments.update', $department) }}">
        @csrf
        @method('PATCH')
        <div class="modal-body">
          @include('admin.organization.partials.department-form', ['departmentItem' => $department, 'users' => $users])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('editDepartmentModal-{{ $department->id }}')">Отмена</button>
          <button type="submit" class="btn btn-primary">Сохранить</button>
        </div>
      </form>
    </div>
  </div>
@endforeach

<div class="modal-overlay" id="createGroupModal" onclick="if(event.target===this)closeModal('createGroupModal')">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Новая группа</div><button type="button" class="modal-close" onclick="closeModal('createGroupModal')">×</button></div>
    <form method="POST" action="{{ route('groups.store') }}">
      @csrf
      <div class="modal-body">
        @include('admin.organization.partials.group-form', ['groupItem' => null, 'users' => $users])
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('createGroupModal')">Отмена</button>
        <button type="submit" class="btn btn-primary">Создать</button>
      </div>
    </form>
  </div>
</div>

@foreach($groups as $group)
  <div class="modal-overlay" id="editGroupModal-{{ $group->id }}" onclick="if(event.target===this)closeModal('editGroupModal-{{ $group->id }}')">
    <div class="modal">
      <div class="modal-header"><div class="modal-title">Редактирование группы</div><button type="button" class="modal-close" onclick="closeModal('editGroupModal-{{ $group->id }}')">×</button></div>
      <form method="POST" action="{{ route('groups.update', $group) }}">
        @csrf
        @method('PATCH')
        <div class="modal-body">
          @include('admin.organization.partials.group-form', ['groupItem' => $group, 'users' => $users])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('editGroupModal-{{ $group->id }}')">Отмена</button>
          <button type="submit" class="btn btn-primary">Сохранить</button>
        </div>
      </form>
    </div>
  </div>
@endforeach

<div class="modal-overlay" id="createCompanyModal" onclick="if(event.target===this)closeModal('createCompanyModal')">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Новая компания-отправитель</div><button type="button" class="modal-close" onclick="closeModal('createCompanyModal')">×</button></div>
    <form method="POST" action="{{ route('companies.store') }}">
      @csrf
      <div class="modal-body">
        @include('admin.organization.partials.company-form', ['companyItem' => null])
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('createCompanyModal')">Отмена</button>
        <button type="submit" class="btn btn-primary">Создать</button>
      </div>
    </form>
  </div>
</div>

@foreach($companies as $company)
  <div class="modal-overlay" id="editCompanyModal-{{ $company->id }}" onclick="if(event.target===this)closeModal('editCompanyModal-{{ $company->id }}')">
    <div class="modal">
      <div class="modal-header"><div class="modal-title">Редактирование компании</div><button type="button" class="modal-close" onclick="closeModal('editCompanyModal-{{ $company->id }}')">×</button></div>
      <form method="POST" action="{{ route('companies.update', $company) }}">
        @csrf
        @method('PATCH')
        <div class="modal-body">
          @include('admin.organization.partials.company-form', ['companyItem' => $company])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('editCompanyModal-{{ $company->id }}')">Отмена</button>
          <button type="submit" class="btn btn-primary">Сохранить</button>
        </div>
      </form>
    </div>
  </div>
@endforeach
@endsection
