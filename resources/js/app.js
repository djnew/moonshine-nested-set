import nestedset from './plugins/nestedset'

document.addEventListener('alpine:init', () => {
    window.Alpine.data('nestedset', nestedset);
})
