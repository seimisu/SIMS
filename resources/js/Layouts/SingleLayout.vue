<template>
    <Toast />
    <div class="min-h-screen w-full flex flex-col lg:flex-row justify-center items-center  bg-blue-500">

        <div class="flex flex-col bg-white justify-center items-center rounded-xl p-3 shadow-lg border border-gray-100">
            <slot name="form"></slot>
        </div>

    </div>
</template>