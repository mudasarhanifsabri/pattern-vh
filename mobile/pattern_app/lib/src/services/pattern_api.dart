import '../models/pattern_models.dart';

class PatternApi {
  const PatternApi({
    this.baseUrl = 'https://rms.pattern.ae/api/mobile',
  });

  final String baseUrl;

  Future<PatternDashboardData> dashboard(PortalRole role) async {
    await Future<void>.delayed(const Duration(milliseconds: 500));
    return role == PortalRole.owner ? _ownerData : _tenantData;
  }
}

const _tenantData = PatternDashboardData(
  name: 'Tenant',
  heroTitle: 'Azizi Riviera 18',
  heroSubtitle: 'Current stay, door access, payments, and support.',
  metrics: [
    PatternMetric(label: 'Check-in', value: '27 Jun', note: '03:00 PM'),
    PatternMetric(label: 'Check-out', value: '20 Jul', note: '11:00 AM'),
    PatternMetric(label: 'Balance', value: 'AED 0', note: 'No pending dues'),
    PatternMetric(label: 'Door code', value: 'Ready', note: 'Active in stay window'),
  ],
  properties: [
    PatternProperty(
      title: 'Unit 705',
      subtitle: 'Azizi Riviera 18',
      status: 'Checked in',
      amount: 'AED 8,100',
    ),
  ],
  activities: [
    PatternActivity(title: 'Smart lock', subtitle: 'Access active for your stay', amount: 'Swipe'),
    PatternActivity(title: 'Support', subtitle: 'Pattern team is available', amount: 'Open'),
  ],
);

const _ownerData = PatternDashboardData(
  name: 'Owner',
  heroTitle: 'Owner Portal',
  heroSubtitle: 'Properties, statements, payouts, and support in one app.',
  metrics: [
    PatternMetric(label: 'Units', value: '2', note: 'Assigned properties'),
    PatternMetric(label: 'Rented', value: '1', note: 'Currently occupied'),
    PatternMetric(label: 'Ready', value: 'AED 0', note: 'Payout transfer'),
    PatternMetric(label: 'Expenses', value: 'AED 320', note: 'Owner linked'),
  ],
  properties: [
    PatternProperty(
      title: 'Unit 705',
      subtitle: 'Azizi Riviera 18',
      status: 'Occupied',
      amount: 'AED 6,000',
    ),
    PatternProperty(
      title: 'Unit 1507',
      subtitle: 'Hera Tower',
      status: 'Available',
      amount: 'AED 6,000',
    ),
  ],
  activities: [
    PatternActivity(title: 'Owner statement', subtitle: 'Rent income and deductions', amount: 'PDF'),
    PatternActivity(title: 'Payouts', subtitle: 'Approved rent collection schedule', amount: 'Open'),
  ],
);
