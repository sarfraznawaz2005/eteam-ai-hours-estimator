function getRandomLoadingImage() {
    const loadingImages = [
        '/assets/giphy1.gif',
        '/assets/giphy2.gif',
        '/assets/giphy3.gif',
        '/assets/giphy4.gif',
        '/assets/giphy5.gif',
        '/assets/giphy6.gif',
        '/assets/giphy7.gif',
        '/assets/giphy8.gif',
        '/assets/giphy9.gif',
        '/assets/giphy10.gif',
        '/assets/giphy11.gif',
        '/assets/giphy12.gif',
    ];
    
    const randomIndex = Math.floor(Math.random() * loadingImages.length);
    
    return loadingImages[randomIndex];
}
