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
                        },
                        body: JSON.stringify({
                            search: this.query,
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
                } catch (error) {
                    this.error = error.message;
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
        <x-filament::input.wrapper
            inline-prefix
            prefix-icon="heroicon-m-magnifying-glass"
        >
            <x-filament::input
                type="search"
                x-model="query"
                placeholder="Search stock images..."
            />
        </x-filament::input.wrapper>

        <div x-show="loading" class="flex justify-center py-8">
            <x-filament::loading-indicator class="h-8 w-8 text-gray-500 dark:text-gray-400" />
        </div>

        <div x-show="error" class="bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    @svg('heroicon-m-exclamation-circle', 'h-5 w-5 text-danger-400 dark:text-danger-300')
                </div>

                <div class="ml-3">
                    <p class="text-sm text-danger-700 dark:text-danger-200" x-text="error"></p>
                </div>
            </div>
        </div>

        <div x-show="!loading && !error && total > 0" class="text-sm text-gray-600 dark:text-gray-400">
            Showing <span x-text="images.length"></span> of <span x-text="total"></span> images
        </div>

        <div x-show="!loading && !error && images.length === 0 && !query.trim()" class="text-center py-12">
            @svg('heroicon-o-photo', 'mx-auto h-12 w-12 text-gray-400 dark:text-gray-500')

            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Search for stock images</h3>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Enter a search term above to find images.</p>
        </div>

        <div x-show="!loading && !error && images.length === 0 && query.trim()" class="text-center py-12">
            @svg('heroicon-o-photo', 'mx-auto h-12 w-12 text-gray-400 dark:text-gray-500')

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
                    />

                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200"></div>
                    
                    <div 
                        x-show="isSelected(image)"
                        class="absolute top-2 right-2 w-6 h-6 bg-primary-500 rounded-full flex items-center justify-center"
                    >
                        @svg('heroicon-m-check', 'w-4 h-4 text-white')
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
                    @svg('heroicon-c-chevron-left', 'w-3 h-3 mr-1')

                    Previous
                </button>

                <div class="flex items-center space-x-2">
                    <template x-for="page in Array.from({ length: Math.min(5, lastPage) }, (_, i) => {
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
                    
                    @svg('heroicon-c-chevron-right', 'w-3 h-3 mr-1')
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
                    @svg('heroicon-m-x-mark', 'w-5 h-5')
                </button>
            </div>
        </div>
    </div>
</x-dynamic-component>
