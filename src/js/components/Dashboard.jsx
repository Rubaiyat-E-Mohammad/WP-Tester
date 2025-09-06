import React, { useState, useEffect } from 'react';
import { 
  Activity, 
  BarChart3, 
  CheckCircle, 
  Clock, 
  Globe, 
  Play, 
  Settings, 
  TrendingUp, 
  Users, 
  Zap,
  AlertTriangle,
  RefreshCw,
  Eye,
  Shield,
  Database
} from 'lucide-react';

const Dashboard = () => {
  const [stats, setStats] = useState({
    totalPages: 0,
    totalFlows: 0,
    recentTests: 0,
    successRate: 0
  });
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('overview');

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      const response = await fetch(wpTesterData.ajaxurl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'wp_tester_get_dashboard_stats',
          nonce: wpTesterData.nonce
        })
      });
      
      const data = await response.json();
      if (data.success) {
        setStats(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  const StatCard = ({ icon: Icon, title, value, change, trend, color = "primary" }) => (
    <div className="stat-card glass-card animate-fade-in-up hover-lift">
      <div className="stat-header">
        <div className={`stat-icon stat-${color}`}>
          <Icon />
        </div>
        {change && (
          <div className={`stat-trend trend-${trend}`}>
            {trend === 'up' ? '↗' : trend === 'down' ? '↘' : '→'}
            {change}%
          </div>
        )}
      </div>
      <div className="stat-value">{loading ? '...' : value}</div>
      <div className="stat-label">{title}</div>
      <div className="stat-progress">
        <div 
          className="progress-bar" 
          style={{ width: loading ? '0%' : `${Math.min(value / 100 * 100, 100)}%` }}
        ></div>
      </div>
      <div className="stat-footer">
        <div className="last-updated">
          <Clock size={12} />
          <span>Just now</span>
        </div>
      </div>
    </div>
  );

  const QuickAction = ({ icon: Icon, title, description, onClick, color = "primary" }) => (
    <div className={`action-card glass-card interactive action-${color}`} onClick={onClick}>
      <div className="action-icon">
        <Icon />
      </div>
      <div className="action-title">{title}</div>
      <div className="action-description">{description}</div>
    </div>
  );

  const ActivityItem = ({ icon: Icon, title, description, time, type = "info" }) => (
    <div className="activity-item">
      <div className={`activity-icon ${type}`}>
        <Icon />
      </div>
      <div className="activity-content">
        <div className="activity-title">{title}</div>
        <div className="activity-description">{description}</div>
        <div className="activity-meta">
          <div className="activity-time">
            <Clock size={12} />
            <span>{time}</span>
          </div>
          <div className="activity-type">{type}</div>
        </div>
      </div>
    </div>
  );

  const SystemStatusItem = ({ name, status, detail }) => (
    <div className="status-item">
      <div className="status-info">
        <div className="status-name">{name}</div>
        <div className="status-detail">{detail}</div>
      </div>
      <div className={`status-indicator status-${status}`}></div>
    </div>
  );

  return (
    <div className="wp-tester-modern">
      {/* Modern Header */}
      <div className="wp-tester-modern-header glass-nav">
        <div className="header-content">
          <div className="logo-section">
            <img 
              src={wpTesterData.pluginData?.logo_url} 
              alt="WP Tester Logo" 
              className="logo animate-float"
            />
            <div className="title-section">
              <h1 className="animate-fade-in">WP Tester</h1>
              <div className="subtitle">Ultra-Modern Testing Dashboard</div>
            </div>
          </div>
          <div className="header-actions">
            <button 
              className="btn btn-outline btn-sm"
              onClick={fetchDashboardData}
              disabled={loading}
            >
              <RefreshCw size={16} className={loading ? 'animate-spin' : ''} />
              Refresh
            </button>
            <button className="btn btn-primary btn-sm">
              <Play size={16} />
              Run Test
            </button>
          </div>
        </div>
      </div>

      <div className="wp-tester-content">
        {/* Stats Grid */}
        <div className="stats-grid stagger-children">
          <StatCard
            icon={Globe}
            title="Pages Crawled"
            value={stats.totalPages}
            change={12}
            trend="up"
            color="primary"
          />
          <StatCard
            icon={Activity}
            title="Active Flows"
            value={stats.totalFlows}
            change={8}
            trend="up"
            color="success"
          />
          <StatCard
            icon={BarChart3}
            title="Tests (24h)"
            value={stats.recentTests}
            change={-3}
            trend="down"
            color="warning"
          />
          <StatCard
            icon={CheckCircle}
            title="Success Rate"
            value={`${stats.successRate}%`}
            change={5}
            trend="up"
            color="success"
          />
        </div>

        {/* Quick Actions */}
        <div className="quick-actions">
          <QuickAction
            icon={Play}
            title="Start New Test"
            description="Run comprehensive testing on all flows"
            onClick={() => console.log('Start test')}
            color="primary"
          />
          <QuickAction
            icon={Eye}
            title="View Reports"
            description="Analyze detailed test results"
            onClick={() => console.log('View reports')}
            color="info"
          />
          <QuickAction
            icon={Settings}
            title="Configure Tests"
            description="Set up testing parameters"
            onClick={() => console.log('Configure')}
            color="secondary"
          />
          <QuickAction
            icon={Shield}
            title="Security Scan"
            description="Check for vulnerabilities"
            onClick={() => console.log('Security scan')}
            color="warning"
          />
        </div>

        {/* Main Content Grid */}
        <div className="grid grid-2 mt-8">
          {/* Recent Activity */}
          <div className="activity-feed glass-card animate-fade-in-left">
            <div className="feed-header">
              <h3>Recent Activity</h3>
              <button className="btn btn-ghost btn-sm">
                View All
              </button>
            </div>
            <div className="activity-list">
              <ActivityItem
                icon={CheckCircle}
                title="Homepage test completed"
                description="All 15 elements passed validation"
                time="2 minutes ago"
                type="success"
              />
              <ActivityItem
                icon={AlertTriangle}
                title="Form submission issue detected"
                description="Contact form validation failed on mobile"
                time="15 minutes ago"
                type="warning"
              />
              <ActivityItem
                icon={TrendingUp}
                title="Performance improved"
                description="Page load time reduced by 23%"
                time="1 hour ago"
                type="success"
              />
              <ActivityItem
                icon={Users}
                title="New flow discovered"
                description="User registration flow added to queue"
                time="2 hours ago"
                type="info"
              />
              <ActivityItem
                icon={Database}
                title="Database optimized"
                description="Test data cleanup completed"
                time="4 hours ago"
                type="info"
              />
            </div>
          </div>

          {/* System Status */}
          <div className="system-status glass-card animate-fade-in-right">
            <div className="status-header">
              <h3>System Status</h3>
              <div className="overall-status status-healthy">
                <div className="status-dot"></div>
                All Systems Operational
              </div>
            </div>
            <div className="status-grid">
              <SystemStatusItem
                name="WordPress"
                status="ok"
                detail="Version 6.4.2 - Up to date"
              />
              <SystemStatusItem
                name="PHP Version"
                status="ok"
                detail="8.1.2 - Compatible"
              />
              <SystemStatusItem
                name="Database"
                status="ok"
                detail="MySQL 8.0 - Optimized"
              />
              <SystemStatusItem
                name="SSL Certificate"
                status="ok"
                detail="Valid - Expires in 89 days"
              />
              <SystemStatusItem
                name="Crawler Status"
                status="ok"
                detail="Running - 0 errors"
              />
              <SystemStatusItem
                name="Storage Space"
                status="warning"
                detail="78% used - 2.1GB available"
              />
            </div>
          </div>
        </div>

        {/* Feature Cards */}
        <div className="grid grid-3 mt-8">
          <div className="feature-card glass-card animate-fade-in-up">
            <div className="feature-icon">
              <Zap />
            </div>
            <div className="feature-title">Lightning Fast</div>
            <div className="feature-description">
              Our advanced testing engine delivers results in seconds, not minutes.
            </div>
            <div className="feature-action">
              <button className="btn btn-primary">Learn More</button>
            </div>
          </div>

          <div className="feature-card glass-card animate-fade-in-up" style={{animationDelay: '0.1s'}}>
            <div className="feature-icon">
              <Shield />
            </div>
            <div className="feature-title">Secure & Reliable</div>
            <div className="feature-description">
              Built with security in mind, ensuring your data stays protected.
            </div>
            <div className="feature-action">
              <button className="btn btn-primary">Learn More</button>
            </div>
          </div>

          <div className="feature-card glass-card animate-fade-in-up" style={{animationDelay: '0.2s'}}>
            <div className="feature-icon">
              <BarChart3 />
            </div>
            <div className="feature-title">Detailed Analytics</div>
            <div className="feature-description">
              Get comprehensive insights with beautiful charts and reports.
            </div>
            <div className="feature-action">
              <button className="btn btn-primary">Learn More</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
