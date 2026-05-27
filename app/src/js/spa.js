document.addEventListener('DOMContentLoaded', () => {
    const reinitializeScripts = (url) => {
        const path = new URL(url, window.location.origin).pathname;
        if (window.lucide) window.lucide.createIcons();
        if (typeof window.initToastMessages === 'function') window.initToastMessages();

        if (path.includes('dashboard.php')) {
            if (typeof window.initDashboard === 'function') window.initDashboard();
        } 
        else if (path.includes('uploads.php')) {
            if (typeof window.initDashboard === 'function') window.initDashboard();
            if (typeof window.initUploads === 'function') window.initUploads();
        }
        else if (path.includes('profile.php') || path.includes('student_profile.php')) {
             if (typeof window.initProfile === 'function') window.initProfile();
        }
        else if (path.includes('settings.php') || path.includes('student_settings.php')) {
             if (typeof window.initSettings === 'function') window.initSettings();
        }
        else if (path.includes('verification_detail.php')) {
             if (typeof window.initVerificationDetail === 'function') window.initVerificationDetail();
        }
        else if (path.includes('file_management.php')) {
             if (typeof window.initFileManagement === 'function') window.initFileManagement();
        }
        else if (path.includes('analytics.php')) {
             if (typeof window.initAdmin === 'function') window.initAdmin();
        }
    };

    let loader = document.getElementById('spa-loader');
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'spa-loader';
        loader.innerHTML = '<div class="spinner"></div>';
        loader.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.95); z-index: 50; display: none; align-items: center; justify-content: center; backdrop-filter: blur(5px); transition: opacity 0.2s ease;';
        
        const spinnerStyle = document.createElement('style');
        spinnerStyle.textContent = `.spinner { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; } @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } } .main-content { position: relative; }`;
        document.head.appendChild(spinnerStyle);
    }

    const handleNavigation = async (url, pushState = true) => {
        const mainContent = document.querySelector('.main-content');
        if (!mainContent) {
            window.location.href = url;
            return;
        }

        if (!loader.parentNode || loader.parentNode !== mainContent) {
            mainContent.appendChild(loader);
        }
        
        loader.style.display = 'flex';

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

            if (data.html) {
                mainContent.innerHTML = data.html;
                
                // Extract and run scripts in the fetched HTML to initialize charts, event listeners, etc.
                const scripts = mainContent.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });

                mainContent.appendChild(loader);
                loader.style.display = 'flex';
            }
            
            if (data.title) {
                document.title = data.title;
            }

            if (pushState) {
                window.history.pushState({}, data.title, url);
            }

            if (data.css_files) {
                await updateCSS(data.css_files);
            }
            if (data.js_files) {
                await updateScripts(data.js_files);
            }

            updateSidebarState(url);
            reinitializeScripts(url);

        } catch (error) {
            console.error('SPA Navigation Error:', error);
            window.location.href = url;
        } finally {
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
                const existing = document.querySelector(`script[src="${src}"]`);
                if (existing) {
                    resolve();
                    return;
                }
                const script = document.createElement('script');
                script.src = src;
                script.onload = resolve;
                script.onerror = resolve;
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
                link.onerror = resolve;
                document.head.appendChild(link);
            });
        });
        return Promise.all(promises);
    };

    const updateSidebarState = (url) => {
        const targetUrl = new URL(url, window.location.origin);
        const targetPath = targetUrl.pathname;
        const menuItems = document.querySelectorAll('.menu-item'); 
        
        menuItems.forEach(item => {
            item.classList.remove('active');
            const text = item.querySelector('.menu-text');
            if(text) text.classList.remove('active-text');
            const bg = item.querySelector('.menu-bg');
            if(bg) bg.remove();

            const iconImg = item.querySelector('.menu-icon img');
            if (iconImg && iconImg.hasAttribute('data-inactive-src')) {
                iconImg.src = iconImg.getAttribute('data-inactive-src');
            }
            
            const itemUrl = new URL(item.href, window.location.origin);
            const itemPath = itemUrl.pathname;

            if (itemPath === targetPath) {
                item.classList.add('active');
                if(text) text.classList.add('active-text');
                
                const bg = document.createElement('div');
                bg.classList.add('menu-bg');
                item.prepend(bg);

                if (iconImg && iconImg.hasAttribute('data-active-src')) {
                    iconImg.src = iconImg.getAttribute('data-active-src');
                }
            }
        });
    };

    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (!link) return;
        if (link.getAttribute('data-no-spa')) return;

        const isMenuItem = link.classList.contains('menu-item') && !link.classList.contains('logout');
        const isSeeMore = link.classList.contains('see-more');
        const isInternalNav = link.href.includes(window.location.origin) && link.href.includes('/app/controllers/');

        if (isMenuItem || isSeeMore || (isInternalNav && !link.classList.contains('logout'))) {
            e.preventDefault();
            handleNavigation(link.href);
        }
    });

    window.addEventListener('popstate', () => {
        handleNavigation(window.location.href, false);
    });

    // Centralized global event delegation for header dropdowns (Notification and Profile)
    document.addEventListener('click', (e) => {
        // A. Profile Dropdown Toggle
        const profileBtn = e.target.closest('.user-profile');
        if (profileBtn) {
            e.stopPropagation();
            const dropdown = profileBtn.querySelector('.profile-dropdown');
            if (dropdown) {
                // Close notification dropdown if open
                const notifDropdown = document.querySelector('.notifications-dropdown');
                if (notifDropdown) notifDropdown.style.display = 'none';

                dropdown.classList.toggle('active');
            }
        } else {
            // Clicked outside - close all profile dropdowns
            document.querySelectorAll('.profile-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }

        // B. Notification Dropdown Toggle
        const notifBtn = e.target.closest('.notification-icon');
        if (notifBtn) {
            e.stopPropagation();
            const dropdown = notifBtn.querySelector('.notifications-dropdown');
            if (dropdown) {
                // Close profile dropdown if open
                const profileDropdown = document.querySelector('.profile-dropdown');
                if (profileDropdown) profileDropdown.classList.remove('active');

                const isVisible = dropdown.style.display === 'block';
                dropdown.style.display = isVisible ? 'none' : 'block';
            }
        } else {
            // Clicked outside - close all notification dropdowns
            document.querySelectorAll('.notifications-dropdown').forEach(dropdown => {
                dropdown.style.display = 'none';
            });
        }
    });
});
