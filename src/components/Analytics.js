/**
 * Analytics Dashboard component
 * Displays session flow and referrer analytics
 */
import { useState, useEffect } from '@wordpress/element';
import { Card, CardBody, SelectControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, search, people, customLink, globe, inbox, megaphone, chartBar } from '@wordpress/icons';

const Analytics = () => {
    const [analyticsData, setAnalyticsData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [dateRange, setDateRange] = useState('30'); // days

    // Fetch analytics data
    useEffect(() => {
        const loadAnalytics = async () => {
            try {
                setLoading(true);
                setError(null);

                const response = await fetch(window.sqtData?.ajaxurl || ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'sqt_get_analytics',
                        nonce: window.sqtData?.nonce || '',
                        days: dateRange
                    })
                });

                const result = await response.json();

                if (result.success) {
                    console.log('Analytics data loaded:', result.data);
                    setAnalyticsData(result.data);
                } else {
                    console.error('Analytics error:', result);
                    setError(result.data || 'Failed to load analytics data');
                }

                setLoading(false);
            } catch (err) {
                console.error('Analytics fetch error:', err);
                setError('Failed to load analytics data: ' + err.message);
                setLoading(false);
            }
        };

        loadAnalytics();
    }, [dateRange]);

    // Format duration (seconds to readable format)
    const formatDuration = (seconds) => {
        if (!seconds || seconds < 60) return `${seconds}s`;

        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;

        if (minutes < 60) {
            return secs > 0 ? `${minutes}m ${secs}s` : `${minutes}m`;
        }

        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
    };

    // Format percentage
    const formatPercent = (value) => {
        return `${Math.round(value)}%`;
    };

    // Get referrer type icon
    const getReferrerIcon = (type) => {
        const icons = {
            search: search,
            social: people,
            direct: customLink,
            referral: globe,
            email: inbox,
            campaign: megaphone
        };
        return icons[type] || chartBar;
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center p-12">
                <Spinner />
                <span className="ml-3">{__('Loading analytics...', 'search-query-tracker')}</span>
            </div>
        );
    }

    if (error) {
        return (
            <div className="bg-red-50 border border-red-200 rounded p-6 m-6">
                <p className="text-red-700">{error}</p>
            </div>
        );
    }

    if (!analyticsData) {
        return (
            <div className="bg-gray-50 border border-gray-200 rounded p-6 m-6">
                <p className="text-gray-600">{__('No analytics data available yet.', 'search-query-tracker')}</p>
            </div>
        );
    }

    const { overview, traffic_sources, landing_pages, search_engines } = analyticsData;

    return (
        <div className="p-6 bg-gray-50 min-h-screen">
            {/* Header with Date Range Filter */}
            <div className="flex justify-between items-center mb-6">
                <h2 className="text-2xl font-bold">{__('Analytics Dashboard', 'search-query-tracker')}</h2>

                <SelectControl
                    label={__('Date Range', 'search-query-tracker')}
                    value={dateRange}
                    options={[
                        { label: __('Last 7 days', 'search-query-tracker'), value: '7' },
                        { label: __('Last 30 days', 'search-query-tracker'), value: '30' },
                        { label: __('Last 90 days', 'search-query-tracker'), value: '90' },
                    ]}
                    onChange={setDateRange}
                />
            </div>

            {/* Overview Stats */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                <Card>
                    <CardBody>
                        <div className="text-gray-500 text-sm mb-1">{__('Sessions', 'search-query-tracker')}</div>
                        <div className="text-3xl font-bold text-gray-900">{overview.total_sessions || 0}</div>
                    </CardBody>
                </Card>

                <Card>
                    <CardBody>
                        <div className="text-gray-500 text-sm mb-1">{__('Pageviews', 'search-query-tracker')}</div>
                        <div className="text-3xl font-bold text-gray-900">{overview.total_pageviews || 0}</div>
                    </CardBody>
                </Card>

                <Card>
                    <CardBody>
                        <div className="text-gray-500 text-sm mb-1">{__('Avg. Pages/Session', 'search-query-tracker')}</div>
                        <div className="text-3xl font-bold text-gray-900">
                            {overview.avg_pages_per_session ? Number(overview.avg_pages_per_session).toFixed(1) : '0'}
                        </div>
                    </CardBody>
                </Card>

                <Card>
                    <CardBody>
                        <div className="text-gray-500 text-sm mb-1">{__('Avg. Duration', 'search-query-tracker')}</div>
                        <div className="text-3xl font-bold text-gray-900">
                            {formatDuration(overview.avg_session_duration || 0)}
                        </div>
                    </CardBody>
                </Card>

                <Card>
                    <CardBody>
                        <div className="text-gray-500 text-sm mb-1">{__('Bounce Rate', 'search-query-tracker')}</div>
                        <div className="text-3xl font-bold text-gray-900">
                            {formatPercent(overview.bounce_rate || 0)}
                        </div>
                    </CardBody>
                </Card>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Traffic Sources */}
                <Card>
                    <CardBody>
                        <h3 className="text-xl font-semibold mb-4">{__('Traffic Sources', 'search-query-tracker')}</h3>

                        {traffic_sources && traffic_sources.length > 0 ? (
                            <div className="space-y-3">
                                {traffic_sources.map((source, index) => {
                                    const percentage = overview.total_sessions > 0
                                        ? (source.sessions / overview.total_sessions) * 100
                                        : 0;

                                    return (
                                        <div key={index} className="border-b border-gray-100 pb-3 last:border-b-0">
                                            <div className="flex justify-between items-center mb-2">
                                                <span className="font-medium flex items-center gap-2">
                                                    <Icon icon={getReferrerIcon(source.referrer_type)} />
                                                    {source.referrer_type_label}
                                                </span>
                                                <span className="text-gray-600">{source.sessions} sessions</span>
                                            </div>
                                            <div className="w-full bg-gray-100 rounded h-2">
                                                <div
                                                    className="h-2 rounded transition-all"
                                                    style={{
                                                        width: `${percentage}%`,
                                                        background: 'linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 154, 0, 0.05) 30%, rgba(255, 0, 64, 0.3) 100%)'
                                                    }}
                                                ></div>
                                            </div>
                                            <div className="text-sm text-gray-500 mt-1">
                                                {formatPercent(percentage)} of total traffic
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <p className="text-gray-500">{__('No traffic source data available', 'search-query-tracker')}</p>
                        )}
                    </CardBody>
                </Card>

                {/* Search Engines */}
                <Card>
                    <CardBody>
                        <h3 className="text-xl font-semibold mb-4">{__('Search Engines', 'search-query-tracker')}</h3>

                        {search_engines && search_engines.length > 0 ? (
                            <div className="space-y-3">
                                {search_engines.map((engine, index) => {
                                    const totalSearchSessions = search_engines.reduce((sum, e) => sum + parseInt(e.sessions), 0);
                                    const percentage = totalSearchSessions > 0
                                        ? (engine.sessions / totalSearchSessions) * 100
                                        : 0;

                                    return (
                                        <div key={index} className="border-b border-gray-100 pb-3 last:border-b-0">
                                            <div className="flex justify-between items-center mb-2">
                                                <span className="font-medium flex items-center gap-2">
                                                    <Icon icon={search} />
                                                    {engine.search_engine}
                                                </span>
                                                <span className="text-gray-600">{engine.sessions} sessions</span>
                                            </div>
                                            <div className="w-full bg-gray-100 rounded h-2">
                                                <div
                                                    className="h-2 rounded transition-all"
                                                    style={{
                                                        width: `${percentage}%`,
                                                        background: 'linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 154, 0, 0.05) 30%, rgba(255, 0, 64, 0.3) 100%)'
                                                    }}
                                                ></div>
                                            </div>
                                            <div className="text-sm text-gray-500 mt-1">
                                                {formatPercent(percentage)} of search traffic
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <p className="text-gray-500">{__('No search engine data available', 'search-query-tracker')}</p>
                        )}
                    </CardBody>
                </Card>
            </div>

            {/* Top Landing Pages */}
            <Card className="mt-6">
                <CardBody>
                    <h3 className="text-xl font-semibold mb-4">{__('Top Landing Pages', 'search-query-tracker')}</h3>

                    {landing_pages && landing_pages.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {__('Page', 'search-query-tracker')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {__('Sessions', 'search-query-tracker')}
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {__('Bounce Rate', 'search-query-tracker')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {landing_pages.map((page, index) => (
                                        <tr key={index} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 text-sm text-gray-900 max-w-md truncate">
                                                {page.landing_page}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {page.sessions}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                <span className={`inline-flex px-2 py-1 rounded-full text-xs font-semibold ${
                                                    page.bounce_rate > 70 ? 'bg-red-100 text-red-800' :
                                                    page.bounce_rate > 50 ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-green-100 text-green-800'
                                                }`}>
                                                    {formatPercent(page.bounce_rate)}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-gray-500">{__('No landing page data available', 'search-query-tracker')}</p>
                    )}
                </CardBody>
            </Card>
        </div>
    );
};

export default Analytics;
