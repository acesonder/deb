/**
 * Analytics page JavaScript with Chart.js visualization
 */

// Blood Pressure Chart
function createBPChart(bpData) {
    const ctx = document.getElementById('bpChart');
    if (!ctx || !bpData || bpData.length === 0) return;
    
    const dates = bpData.map(d => d.date);
    const systolic = bpData.map(d => parseFloat(d.avg_systolic));
    const diastolic = bpData.map(d => parseFloat(d.avg_diastolic));
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates.map(d => {
                const date = new Date(d);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }),
            datasets: [
                {
                    label: 'Systolic BP',
                    data: systolic,
                    borderColor: '#ff6b6b',
                    backgroundColor: 'rgba(255, 107, 107, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Diastolic BP',
                    data: diastolic,
                    borderColor: '#4ecdc4',
                    backgroundColor: 'rgba(78, 205, 196, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Target Max (140/90)',
                    data: Array(dates.length).fill(140),
                    borderColor: '#ffa500',
                    borderDash: [5, 5],
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Blood Pressure Trends',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += Math.round(context.parsed.y) + ' mmHg';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Blood Pressure (mmHg)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });
}

// Heart Rate Chart
function createHeartRateChart(hrData) {
    const ctx = document.getElementById('hrChart');
    if (!ctx || !hrData || hrData.length === 0) return;
    
    const dates = hrData.map(d => d.date);
    const heartRates = hrData.map(d => parseFloat(d.avg_hr));
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dates.map(d => {
                const date = new Date(d);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }),
            datasets: [{
                label: 'Heart Rate',
                data: heartRates,
                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                borderColor: 'rgb(255, 99, 132)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Heart Rate Over Time',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Heart Rate (BPM)'
                    }
                }
            }
        }
    });
}

// Mood Distribution Pie Chart
function createMoodChart(moodData) {
    const ctx = document.getElementById('moodChart');
    if (!ctx || !moodData || Object.keys(moodData).length === 0) return;
    
    const moodLabels = {
        'excellent': '😄 Excellent',
        'good': '🙂 Good',
        'fair': '😐 Fair',
        'poor': '😟 Poor',
        'critical': '😰 Critical'
    };
    
    const moodColors = {
        'excellent': '#4caf50',
        'good': '#8bc34a',
        'fair': '#ffc107',
        'poor': '#ff9800',
        'critical': '#f44336'
    };
    
    const labels = [];
    const data = [];
    const colors = [];
    
    for (let mood in moodData) {
        labels.push(moodLabels[mood] || mood);
        data.push(moodData[mood]);
        colors.push(moodColors[mood] || '#999');
    }
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'right'
                },
                title: {
                    display: true,
                    text: 'Mood Distribution',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                }
            }
        }
    });
}

// Correlation Analysis - BP vs Stress
function createCorrelationChart(correlationData) {
    const ctx = document.getElementById('correlationChart');
    if (!ctx || !correlationData || correlationData.length === 0) return;
    
    const data = correlationData.map(d => ({
        x: parseFloat(d.stress_level),
        y: parseFloat(d.avg_systolic),
        r: 5
    }));
    
    new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'BP vs Stress Correlation',
                data: data,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgb(54, 162, 235)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Blood Pressure vs Stress Level',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Stress Level (1-10)'
                    },
                    min: 0,
                    max: 10
                },
                y: {
                    title: {
                        display: true,
                        text: 'Systolic BP (mmHg)'
                    }
                }
            }
        }
    });
}

// Heat Map for Pattern Recognition
function createHeatMap(heatmapData) {
    const ctx = document.getElementById('heatmapChart');
    if (!ctx || !heatmapData || heatmapData.length === 0) return;
    
    // Process data for heatmap - showing BP readings by day of week and time of day
    const labels = heatmapData.map(d => d.label);
    const data = heatmapData.map(d => ({
        x: d.x,
        y: d.y,
        v: d.value
    }));
    
    // This is a simplified version - you might want to use a dedicated heatmap library
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Average BP by Time Pattern',
                data: heatmapData.map(d => d.value),
                backgroundColor: heatmapData.map(d => {
                    if (d.value >= 140) return 'rgba(255, 99, 132, 0.8)';
                    if (d.value >= 130) return 'rgba(255, 159, 64, 0.8)';
                    return 'rgba(75, 192, 192, 0.8)';
                })
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Blood Pressure Patterns',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                }
            },
            scales: {
                y: {
                    title: {
                        display: true,
                        text: 'Average Systolic BP (mmHg)'
                    }
                }
            }
        }
    });
}

// Week over Week Comparison
function createWeekComparisonChart(weekData) {
    const ctx = document.getElementById('weekComparisonChart');
    if (!ctx || !weekData || weekData.length === 0) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: weekData.map(w => w.week_label),
            datasets: [
                {
                    label: 'Average Systolic BP',
                    data: weekData.map(w => parseFloat(w.avg_systolic)),
                    backgroundColor: 'rgba(255, 107, 107, 0.5)',
                    borderColor: 'rgb(255, 107, 107)',
                    borderWidth: 1
                },
                {
                    label: 'Average Diastolic BP',
                    data: weekData.map(w => parseFloat(w.avg_diastolic)),
                    backgroundColor: 'rgba(78, 205, 196, 0.5)',
                    borderColor: 'rgb(78, 205, 196)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Week-over-Week Blood Pressure Comparison',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                }
            },
            scales: {
                y: {
                    title: {
                        display: true,
                        text: 'Blood Pressure (mmHg)'
                    }
                }
            }
        }
    });
}

// Initialize all charts on page load
document.addEventListener('DOMContentLoaded', function() {
    // Charts will be initialized from PHP data
    console.log('Analytics charts loaded');
});
