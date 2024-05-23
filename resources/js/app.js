import nestedset from './plugins/nested-set'

document.addEventListener('alpine:init', () => {
    window.Alpine.data('nestedset', nestedset);
})
