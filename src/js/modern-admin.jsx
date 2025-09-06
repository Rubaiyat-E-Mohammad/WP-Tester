import React from 'react';
import { createRoot } from 'react-dom/client';
import Dashboard from './components/Dashboard';

// Modern Admin Interface Initializer
class WPTesterModernAdmin {
  constructor() {
    this.init();
  }

  init() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.mountComponents());
    } else {
      this.mountComponents();
    }
  }

  mountComponents() {
    // Mount React Dashboard if container exists
    const dashboardContainer = document.getElementById('wp-tester-modern-dashboard');
    if (dashboardContainer) {
      const root = createRoot(dashboardContainer);
      root.render(<Dashboard />);
    }

    // Initialize other modern UI features
    this.initAnimations();
    this.initScrollReveal();
    this.initTooltips();
    this.initThemeToggle();
  }

  initAnimations() {
    // Add animation classes to elements as they come into view
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('animate-fade-in-up');
          observer.unobserve(entry.target);
        }
      });
    });

    animatedElements.forEach(el => observer.observe(el));
  }

  initScrollReveal() {
    // Scroll reveal animation system
    const revealElements = document.querySelectorAll('.scroll-reveal');
    
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    });

    revealElements.forEach(el => revealObserver.observe(el));
  }

  initTooltips() {
    // Modern tooltip system
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
      let tooltip = null;
      
      element.addEventListener('mouseenter', (e) => {
        const text = e.target.getAttribute('data-tooltip');
        tooltip = this.createTooltip(text);
        document.body.appendChild(tooltip);
        this.positionTooltip(tooltip, e.target);
        
        // Show tooltip with animation
        setTimeout(() => tooltip.classList.add('show'), 10);
      });
      
      element.addEventListener('mouseleave', () => {
        if (tooltip) {
          tooltip.classList.remove('show');
          setTimeout(() => {
            if (tooltip && tooltip.parentNode) {
              tooltip.parentNode.removeChild(tooltip);
            }
          }, 150);
        }
      });
    });
  }

  createTooltip(text) {
    const tooltip = document.createElement('div');
    tooltip.className = 'glass-tooltip';
    tooltip.textContent = text;
    return tooltip;
  }

  positionTooltip(tooltip, target) {
    const targetRect = target.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    
    const top = targetRect.top - tooltipRect.height - 10;
    const left = targetRect.left + (targetRect.width - tooltipRect.width) / 2;
    
    tooltip.style.position = 'fixed';
    tooltip.style.top = `${top}px`;
    tooltip.style.left = `${left}px`;
    tooltip.style.zIndex = '1070';
  }

  initThemeToggle() {
    // Theme toggle functionality (for future dark mode)
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
      themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('wp-tester-theme', 
          document.body.classList.contains('dark-mode') ? 'dark' : 'light'
        );
      });
      
      // Apply saved theme
      const savedTheme = localStorage.getItem('wp-tester-theme');
      if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
      }
    }
  }

  // Utility function to show modern notifications
  static showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `glass-notification notification-${type} notification-slide-in`;
    
    const iconMap = {
      info: '💡',
      success: '✅',
      warning: '⚠️',
      error: '❌'
    };
    
    notification.innerHTML = `
      <div class="notification-icon">${iconMap[type]}</div>
      <div class="notification-content">
        <div class="notification-message">${message}</div>
      </div>
      <button class="notification-close" onclick="this.parentElement.remove()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    `;
    
    // Position notification
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '1080';
    
    document.body.appendChild(notification);
    
    // Auto remove
    setTimeout(() => {
      notification.classList.add('notification-slide-out');
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 300);
    }, duration);
  }

  // Utility function for loading states
  static setLoading(element, loading = true) {
    if (loading) {
      element.classList.add('loading');
      element.setAttribute('disabled', 'disabled');
    } else {
      element.classList.remove('loading');
      element.removeAttribute('disabled');
    }
  }

  // Utility function for smooth scrolling
  static smoothScrollTo(target) {
    const element = typeof target === 'string' ? document.querySelector(target) : target;
    if (element) {
      element.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  }
}

// Initialize when WordPress admin is ready
window.addEventListener('DOMContentLoaded', () => {
  // Only initialize on WP Tester admin pages
  if (document.body.classList.contains('wp-tester-admin') || 
      document.querySelector('#wp-tester-modern-dashboard')) {
    window.wpTesterModernAdmin = new WPTesterModernAdmin();
  }
});

// Export utilities for global use
window.WPTesterUI = {
  showNotification: WPTesterModernAdmin.showNotification,
  setLoading: WPTesterModernAdmin.setLoading,
  smoothScrollTo: WPTesterModernAdmin.smoothScrollTo
};

export default WPTesterModernAdmin;
