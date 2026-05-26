@props([
    'show' => 'deleteModal.show',
    'title' => 'Delete Item',
    'name' => null,
    'message' => null,
    'confirmAction' => null,
    'cancelAction' => 'deleteModal.show = false',
    'loading' => 'deleteModal.deleting',
    'confirmText' => 'Delete',
    'loadingText' => 'Deleting...',
])

<div x-show="{{ $show }}" class="fixed inset-0 z-[2000] overflow-y-auto" aria-labelledby="delete-modal-title" role="dialog" aria-modal="true" style="display: none;">
    <div class="relative z-[2000] flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
        <div class="fixed inset-0 z-[2000] bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
        <div class="relative z-[2010] inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <h3 class="text-lg font-medium leading-6 text-gray-900" id="delete-modal-title">{{ $title }}</h3>
                <p class="mt-2 text-sm text-gray-500">
                    @if($message)
                        {!! $message !!}
                    @else
                        Are you sure you want to delete
                        @if($name)
                            "<span x-text="{{ $name }}"></span>"
                        @else
                            this item
                        @endif
                        ? This action cannot be undone.
                    @endif
                </p>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <button @if($confirmAction) @click="{{ $confirmAction }}" @endif :disabled="{{ $loading }}" type="button" class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 disabled:opacity-50 sm:ml-3 sm:w-auto sm:text-sm">
                    <span x-show="!{{ $loading }}">{{ $confirmText }}</span>
                    <span x-show="{{ $loading }}">{{ $loadingText }}</span>
                </button>
                <button @click="{{ $cancelAction }}" type="button" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm">Cancel</button>
            </div>
        </div>
    </div>
</div>
