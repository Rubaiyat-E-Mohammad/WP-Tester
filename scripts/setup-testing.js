#!/usr/bin/env node

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('🚀 WP Tester: Setting up browser automation...');

try {
    // Check if we're in the right directory
    if (!fs.existsSync('package.json')) {
        console.error('❌ Error: package.json not found. Please run this from the WP-Tester root directory.');
        process.exit(1);
    }

    // Install Playwright browsers
    console.log('📦 Installing Playwright browsers...');
    try {
        execSync('npx playwright install', { stdio: 'inherit' });
        console.log('✅ Playwright browsers installed successfully');
    } catch (error) {
        console.warn('⚠️  Playwright installation failed:', error.message);
    }

    // Check Selenium
    console.log('🔍 Checking Selenium...');
    try {
        execSync('selenium-standalone --version', { stdio: 'pipe' });
        console.log('✅ Selenium is available');
        
        // Try to install Selenium drivers
        try {
            execSync('selenium-standalone install', { stdio: 'inherit' });
            console.log('✅ Selenium drivers installed');
        } catch (error) {
            console.warn('⚠️  Selenium driver installation failed:', error.message);
        }
    } catch (error) {
        console.warn('⚠️  Selenium not available. Install with: npm i -g selenium-standalone');
    }

    // Create a status file
    const statusFile = path.join(__dirname, '..', '.testing-setup-complete');
    fs.writeFileSync(statusFile, new Date().toISOString());
    
    console.log('🎉 Browser automation setup complete!');
    console.log('💡 You can now run tests with real browser automation.');
    
} catch (error) {
    console.error('❌ Setup failed:', error.message);
    process.exit(1);
}
