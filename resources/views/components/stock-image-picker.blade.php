<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div 
        x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            query: '',
            images: [],
            currentPage: 1,
            lastPage: 1,
            total: 0,
            loading: false,
            error: null,
            selectedImage: null,
            searchTimeout: null,
            
            init() {
                this.$watch('query', () => {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        this.currentPage = 1;
                        this.loadImages();
                    }, 500);
                });
            },
            
            async loadImages() {
                if (!this.query.trim()) {
                    this.images = [];
                    this.currentPage = 1;
                    this.lastPage = 1;
                    this.total = 0;
                    return;
                }
                
                this.loading = true;
                this.error = null;
                
                try {
                    const response = await fetch(@js($getUrl()), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
                        },
                        body: JSON.stringify({
                            query: this.query,
                            page: this.currentPage
                        })
                    });
                    
                    if (!response.ok) {
                        throw new Error('Failed to fetch images');
                    }
                    
                    const data = await response.json();
                    this.images = data.data || [];
                    this.currentPage = data.current_page || 1;
                    this.lastPage = data.last_page || 1;
                    this.total = data.total || 0;
                } catch (err) {
                    this.error = err.message;
                } finally {
                    this.loading = false;
                }
            },
            
            selectImage(image) {
                this.selectedImage = image;
                this.state = {
                    src: image.url,
                    alt: image.title
                };
            },
            
            goToPage(page) {
                if (page >= 1 && page <= this.lastPage && page !== this.currentPage) {
                    this.currentPage = page;
                    this.loadImages();
                }
            },
            
            isSelected(image) {
                return this.selectedImage && this.selectedImage.url === image.url;
            }
        }"
        class="space-y-4"
    >
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input
                type="text"
                x-model="query"
                placeholder="Search stock images..."
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 dark:focus:placeholder-gray-500 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 text-sm"
            >
        </div>

        <div x-show="loading" class="flex justify-center py-8">
            <x-filament::loading-indicator class="h-8 w-8 text-gray-500 dark:text-gray-400" />
        </div>

        <div x-show="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400 dark:text-red-300" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
                </div>
            </div>
        </div>

        <div x-show="!loading && !error && total > 0" class="text-sm text-gray-600 dark:text-gray-400">
            Showing <span x-text="images.length"></span> of <span x-text="total"></span> images
        </div>

        <div x-show="!loading && !error && images.length === 0 && !query.trim()" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Search for stock images</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Enter a search term above to find images.</p>
        </div>

        <div x-show="!loading && !error && images.length === 0 && query.trim()" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No images found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search terms.</p>
        </div>

        <div x-show="!loading && !error && images.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <template x-for="image in images" x-bind:key="image.url">
                <div 
                    x-on:click="selectImage(image)"
                    x-bind:class="{
                        'ring-2 ring-primary-500 ring-offset-2 dark:ring-offset-gray-800': isSelected(image),
                        'hover:ring-2 hover:ring-gray-300 dark:hover:ring-gray-600 hover:ring-offset-2 dark:hover:ring-offset-gray-800': !isSelected(image)
                    }"
                    class="relative aspect-square bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden cursor-pointer transition-all duration-200 group"
                >
                    <img 
                        x-bind:src="image.preview_url" 
                        x-bind:alt="image.title"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                        loading="lazy"
                    >
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200"></div>
                    
                    <div 
                        x-show="isSelected(image)"
                        class="absolute top-2 right-2 w-6 h-6 bg-primary-500 rounded-full flex items-center justify-center"
                    >
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <p class="text-white text-xs font-medium truncate" x-text="image.title"></p>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="!loading && !error && lastPage > 1" class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <button
                    x-on:click="goToPage(currentPage - 1)"
                    x-bind:disabled="currentPage === 1"
                    type="button"
                    x-bind:class="{
                        'opacity-50 cursor-not-allowed': currentPage === 1,
                        'hover:text-primary-600 dark:hover:text-primary-400': currentPage !== 1
                    }"
                    class="text-sm text-gray-500 dark:text-gray-400 transition-colors duration-200 flex items-center"
                >
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Previous
                </button>

                <div class="flex items-center space-x-2">
                    <template x-for="page in Array.from({length: Math.min(5, lastPage)}, (_, i) => {
                        const start = Math.max(1, Math.min(currentPage - 2, lastPage - 4));
                        return start + i;
                    }).filter(p => p <= lastPage)" x-bind:key="page">
                        <button
                            x-on:click="goToPage(page)"
                            type="button"
                            x-bind:class="{
                                'text-primary-600 dark:text-primary-400 font-medium': page === currentPage,
                                'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': page !== currentPage
                            }"
                            class="text-sm px-2 py-1 rounded transition-colors duration-200"
                            x-text="page"
                        ></button>
                    </template>
                </div>

                <button
                    x-on:click="goToPage(currentPage + 1)"
                    x-bind:disabled="currentPage === lastPage"
                    type="button"
                    x-bind:class="{
                        'opacity-50 cursor-not-allowed': currentPage === lastPage,
                        'hover:text-primary-600 dark:hover:text-primary-400': currentPage !== lastPage
                    }"
                    class="text-sm text-gray-500 dark:text-gray-400 transition-colors duration-200 flex items-center"
                >
                    Next
                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <span class="text-sm text-gray-500 dark:text-gray-400">
                Page <span x-text="currentPage"></span> of <span x-text="lastPage"></span>
            </span>
        </div>

        <div x-show="selectedImage" class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Selected Image:</h4>
            <div class="flex items-center space-x-3">
                <img 
                    x-bind:src="selectedImage?.preview_url" 
                    x-bind:alt="selectedImage?.title"
                    class="w-16 h-16 object-cover rounded-lg"
                >
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="selectedImage?.title"></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate" x-text="selectedImage?.url"></p>
                </div>
                <button
                    x-on:click="selectedImage = null; state = null"
                    type="button"
                    class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</x-dynamic-component>
