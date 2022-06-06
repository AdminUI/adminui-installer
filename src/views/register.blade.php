<x-adminui-installer::layout title="AdminUI Installer: Register">
    <div class="flex justify-center mb-4">
        <x-adminui-installer::logo width="w-20"></x-adminui-installer::logo>
    </div>
    <p class="mb-4">AdminUI has been successfully installed. Enter your details below to create an admin
        account.</p>
    <div v-scope>
        <form @submit.prevent="onSubmit">
            <div class="grid grid-cols-2 gap-x-4">
                <div>
                    <x-adminui-installer::input-text model="first_name" placeholder="First Name">
                        <x-slot:icon>
                            <svg class="w-6 h-6 mr-2" viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" />
                            </svg>
                        </x-slot:icon>
                    </x-adminui-installer::input-text>
                    <div v-text="errors.first_name" class="error-message text-red-300 text-sm h-8 flex items-center">
                    </div>
                </div>
                <div>
                    <x-adminui-installer::input-text model="last_name" placeholder="Last Name">
                        <x-slot:icon>
                            <svg class="w-6 h-6 mr-2" viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" />
                            </svg>
                        </x-slot:icon>
                    </x-adminui-installer::input-text>
                    <div v-text="errors.last_name" class="error-message text-red-300 text-sm h-8 flex items-center">
                    </div>
                </div>
                <div class="col-span-2">
                    <x-adminui-installer::input-text model="email" placeholder="Email Address">
                        <x-slot:icon>
                            <svg class="w-6 h-6 mr-2" viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z" />
                            </svg>
                        </x-slot:icon>
                    </x-adminui-installer::input-text>
                    <div v-text="errors.email" class="error-message text-red-300 text-sm h-8 flex items-center"></div>
                </div>
                <div class="col-span-2">
                    <x-adminui-installer::input-text model="password" placeholder="Password">
                        <x-slot:icon>
                            <svg class="w-6 h-6 mr-2" viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="M12,17A2,2 0 0,0 14,15C14,13.89 13.1,13 12,13A2,2 0 0,0 10,15A2,2 0 0,0 12,17M18,8A2,2 0 0,1 20,10V20A2,2 0 0,1 18,22H6A2,2 0 0,1 4,20V10C4,8.89 4.9,8 6,8H7V6A5,5 0 0,1 12,1A5,5 0 0,1 17,6V8H18M12,3A3,3 0 0,0 9,6V8H15V6A3,3 0 0,0 12,3Z" />
                            </svg>
                        </x-slot:icon>
                    </x-adminui-installer::input-text>
                    <div v-text="errors.password" class="error-message text-red-300 text-sm h-8 flex items-center">
                    </div>
                </div>
                <div class="col-span-2">
                    <x-adminui-installer::input-text model="password_confirmation" placeholder="Confirm Password">
                        <x-slot:icon>
                            <svg class="w-6 h-6 mr-2" viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="M10 17C11.1 17 12 16.1 12 15C12 13.9 11.1 13 10 13C8.9 13 8 13.9 8 15S8.9 17 10 17M16 8C17.1 8 18 8.9 18 10V20C18 21.1 17.1 22 16 22H4C2.9 22 2 21.1 2 20V10C2 8.9 2.9 8 4 8H5V6C5 3.2 7.2 1 10 1S15 3.2 15 6V8H16M10 3C8.3 3 7 4.3 7 6V8H13V6C13 4.3 11.7 3 10 3M22 13H20V7H22V13M22 17H20V15H22V17Z" />
                            </svg>
                        </x-slot:icon>
                    </x-adminui-installer::input-text>
                    <div v-text="errors.password_confirmation"
                        class="error-message text-red-300 text-sm h-8 flex items-center"></div>
                </div>
            </div>
            <div class="flex justify-end">
                <x-adminui-installer::button loading="isLoading" type="submit">
                    <x-slot:icon>
                        <svg class="w-6 h-6 -ml-1 mr-2" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                d="M6 8C6 5.79 7.79 4 10 4S14 5.79 14 8 12.21 12 10 12 6 10.21 6 8M10 14C5.58 14 2 15.79 2 18V20H13.09C13.04 19.67 13 19.34 13 19C13 17.36 13.66 15.87 14.74 14.78C13.41 14.29 11.78 14 10 14M19 15L16 18H18V22H20V18H22L19 15Z" />
                        </svg>
                    </x-slot:icon>
                    Register
                </x-adminui-installer::button>
            </div>
        </form>
    </div>

    <x-slot:scripts>
        <script type="module">
            const jsonHeaders = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }

            import {
                createApp
            } from 'https://unpkg.com/petite-vue?module';

            createApp({
                first_name: "",
                last_name: "",
                email: "",
                password: "",
                password_confirmation: "",
                errors: {
                    first_name: "",
                    last_name: "",
                    email: "",
                    password: "",
                    password_confirmation: "",
                },
                isLoading: false,
                async onSubmit() {
                    this.isLoading = true;
                    console.log("Here");
                }
            }).mount();
        </script>
    </x-slot:scripts>
</x-adminui-installer::layout>
