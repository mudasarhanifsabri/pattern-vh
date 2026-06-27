enum PortalRole { tenant, owner }

class PatternMetric {
  const PatternMetric({
    required this.label,
    required this.value,
    required this.note,
  });

  final String label;
  final String value;
  final String note;
}

class PatternProperty {
  const PatternProperty({
    required this.title,
    required this.subtitle,
    required this.status,
    required this.amount,
  });

  final String title;
  final String subtitle;
  final String status;
  final String amount;
}

class PatternActivity {
  const PatternActivity({
    required this.title,
    required this.subtitle,
    required this.amount,
  });

  final String title;
  final String subtitle;
  final String amount;
}

class PatternDashboardData {
  const PatternDashboardData({
    required this.name,
    required this.heroTitle,
    required this.heroSubtitle,
    required this.metrics,
    required this.properties,
    required this.activities,
  });

  final String name;
  final String heroTitle;
  final String heroSubtitle;
  final List<PatternMetric> metrics;
  final List<PatternProperty> properties;
  final List<PatternActivity> activities;
}
