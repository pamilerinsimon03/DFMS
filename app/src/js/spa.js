/**
 * SPA Navigation Router for DFMS
 * Handles seamless page transitions without full reloads.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Determine strict base URL to avoid matching creating new relative logic errors
    // We assume all internal links we want to SPA are relative or same-origin.

    const reinitializeScripts = (url) => {
        const path = new URL(url, window.location.origin).pathname;

        // Global re-inits
        if (window.lucide) window.lucide.createIcons();
        if (window.toast) { 
            // Toast usually doesn't need re-init, it's global object.
        }

        // Page specific re-inits
        // We match simplified paths or filenames
        if (path.includes('dashboard.php')) {
            if (typeof window.initDashboard === 'function') window.initDashboard();
        } 
        else if (path.includes('uploads.php')) {
            // Uploads.php includes dashboard.js too, so run both?
            // Usually dashboard.js init is safe to run.
            if (typeof window.initDashboard === 'function') window.initDashboard();
            if (typeof window.initUploads === 'function') window.initUploads();
        }
        else if (path.includes('profile.php') || path.includes('student_profile.php')) {
            // If we have profile scripts
             if (typeof window.initProfile === 'function') window.initProfile();
        }
        else if (path.includes('settings.php') || path.includes('student_settings.php')) {
             if (typeof window.initSettings === 'function') window.initSettings();
        }
    };

    // Create Loader element if not exists
    let loader = document.getElementById('spa-loader');
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'spa-loader';
        loader.innerHTML = '<div class="spinner"></div>';
        // Changed to absolute positioning to cover only main content or targeted area
        // We'll append it to the body but style it to overlay, OR strictly append to main-content when needed?
        // Better: Make it fixed but with z-index accessible, OR, better yet, just overlay main content.
        // Let's rely on injecting it into .main-content wrapper relative position.
        
        // Heavily obscure the content to prevent FOUC
        loader.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.95); z-index: 50; display: none; align-items: center; justify-content: center; backdrop-filter: blur(5px); transition: opacity 0.2s ease;';
        
        const spinnerStyle = document.createElement('style');
        spinnerStyle.textContent = `.spinner { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; } @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } } .main-content { position: relative; }`;
        document.head.appendChild(spinnerStyle);
        // We don't append to body yet, we append to main-content dynamically or check existence
    }

    const handleNavigation = async (url, pushState = true) => {
        const mainContent = document.querySelector('.main-content');
        if (!mainContent) {
            window.location.href = url; // Fallback if no container
            return;
        }

        // Setup loader inside main-content
        if (!loader.parentNode || loader.parentNode !== mainContent) {
            mainContent.appendChild(loader);
        }
        
        // Show loader
        loader.style.display = 'flex';
        // Remove opacity change as loader covers it now with semi-transparent white
        // mainContent.style.opacity = '0.5';

        try {
            const response = await fetch(url, {
                headers: {
                    'X-SPA-Request': 'true',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                window.location.href = url;
                return;
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                window.location.href = url;
                return;
            }

            const data = await response.json();

            // 1. Update Content
            if (mainContent && data.html) {
                // Be careful not to wipe the loader if it's inside mainContent
                // Strategy: Update innerHTML but then re-append loader? 
                // Or better: The new HTML replaces everything.
                // We want the loader to persist UNTIL scripts/css are ready.
                // So... we might need the loader OUTSIDE main-content or handle it carefully.
                // BUT user wants sidebar static.
                // Re-think: The loader should be an overlay ON TOP of main-content.
                // If we replace innerHTML, we lose the loader if it is a child.
                // Solution: Create a 'content-wrapper' inside main-content? Or just re-append loader immediately.
                // Or better: The loader is fixed/absolute but positioned over the main-content rect? Complex.
                // Simple Solution: Opacity on main-content + Global Loader that is shifted? 
                // User asked for "only the table should do that".
                // Let's try appending loader to `document.body` but with `z-index` and transparent background, 
                // but ONLY masking the main content... hard without structure changes.
                
                // Best simple approach:
                // Use the transparency trick + Top Progress Bar? 
                // Let's go with: Loader inside main-content.
                // When we receive data:
                // We keep loader visible.
                // We replace `mainContent.innerHTML` but PREPEND the loader back into it immediately.
                
                mainContent.innerHTML = data.html;
                mainContent.appendChild(loader); // Re-add loader immediately
                loader.style.display = 'flex'; // Ensure it's still visible
            }
            
            // 2. Update Title
            if (data.title) {
                document.title = data.title;
            }

            // 3. Update URL
            if (pushState) {
                window.history.pushState({}, data.title, url);
            }

            // 5. Update CSS & Scripts
            if (data.css_files) {
                await updateCSS(data.css_files);
            }
            if (data.js_files) {
                await updateScripts(data.js_files);
            }

            // 6. Update Sidebar Active State
            updateSidebarState(url);

            // 7. Re-initialize Scripts
            reinitializeScripts(url);

        } catch (error) {
            console.error('SPA Navigation Error:', error);
            window.location.href = url; // Fallback
        } finally {
            // Hide loader after a short buffer
            setTimeout(() => {
                if(loader) loader.style.display = 'none';
            }, 50); 
        }
    };

    const updateScripts = (jsFiles) => {
        if (!jsFiles) return Promise.resolve();
        const promises = jsFiles.map(file => {
            return new Promise((resolve) => {
                const src = `/app/src/js/${file}`;
                // Check if already exists
                const existing = document.querySelector(`script[src="${src}"]`);
                if (existing) {
                    resolve();
                    return;
                }
                const script = document.createElement('script');
                script.src = src;
                script.onload = resolve;
                script.onerror = resolve; // Continue even if fails
                document.body.appendChild(script);
            });
        });
        return Promise.all(promises);
    };

    const updateCSS = (cssFiles) => {
        const promises = cssFiles.map(file => {
            return new Promise((resolve) => {
                const existing = document.querySelector(`link[href*="${file}"]`);
                if (existing) {
                    resolve();
                    return;
                }
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = `/app/src/css/${file}`;
                link.onload = resolve;
                link.onerror = resolve; // Continue even if css fails
                document.head.appendChild(link);
            });
        });
        return Promise.all(promises);
    };

    const updateSidebarState = (url) => {
        // Fix: Use pathname matching to ignore query params explicitly
        const targetUrl = new URL(url, window.location.origin);
        const targetPath = targetUrl.pathname;
        
        const menuItems = document.querySelectorAll('.menu-item'); 
        
        menuItems.forEach(item => {
            item.classList.remove('active');
            const text = item.querySelector('.menu-text');
            if(text) text.classList.remove('active-text');
            const bg = item.querySelector('.menu-bg');
            if(bg) bg.remove();

            // Reset icon
            const iconImg = item.querySelector('.menu-icon img');
            if (iconImg && iconImg.hasAttribute('data-inactive-src')) {
                iconImg.src = iconImg.getAttribute('data-inactive-src');
            }
            
            // Check match: Compare pathnames
            const itemUrl = new URL(item.href, window.location.origin);
            const itemPath = itemUrl.pathname;

            // We match if paths are identical
            if (itemPath === targetPath) {
                item.classList.add('active');
                if(text) text.classList.add('active-text');
                
                const bg = document.createElement('div');
                bg.classList.add('menu-bg');
                item.prepend(bg);

                // Set icon active
                if (iconImg && iconImg.hasAttribute('data-active-src')) {
                    iconImg.src = iconImg.getAttribute('data-active-src');
                }
            }
        });
    };

    // Intercept Links
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (!link) return;
        if (link.getAttribute('data-no-spa')) return;
        
        // Exclude Admin Pages from SPA
        if (link.href.includes('admin.php') || link.href.includes('verifications.php') || link.href.includes('students.php') || link.href.includes('add_student.php')) return;

        const isMenuItem = link.classList.contains('menu-item') && !link.classList.contains('logout');
        const isSeeMore = link.classList.contains('see-more');
        const isInternalNav = link.href.includes(window.location.origin) && link.href.includes('/app/controllers/');

        if (isMenuItem || isSeeMore || (isInternalNav && !link.classList.contains('logout'))) {
            e.preventDefault();
            handleNavigation(link.href);
        }
    });

    // Handle Back/Forward buttons
    window.addEventListener('popstate', () => {
        handleNavigation(window.location.href, false);
    });
});
