"""Execute the real handler with fault-injected, memory-only WordPress storage.

These tests do not prove production database durability or mail delivery.
"""
import json

import pytest

from test_group_request_friction import group_payload, invoke


def persist(options=None, **changes):
    payload = group_payload()
    payload.update(changes)
    payload['_fixture'] = options or {}
    return json.loads(invoke('persist', payload=payload))


@pytest.mark.parametrize('language', ['he', 'en'])
def test_success_requires_complete_private_record_and_readback(language):
    result = persist(language=language, message='טחינה "בצד" \\ note',
                     preference_summary='8 מנות צמחוניות', budget_per_person='45.50')
    assert result['result'] == 'redirected'
    assert result['redirect'] == 'https://complete99.co.il/request-proposal/?c99_sent=1'
    assert result['fresh_reads'] == 1
    assert result['posts']['101']['post_type'] == 'c99_lead'
    assert result['posts']['101']['post_status'] == 'private'
    meta = result['meta']['101']
    assert meta['_c99_language'] == language
    assert meta['_c99_message'] == 'טחינה "בצד" \\ note'
    assert meta['_c99_preference_summary'] == '8 מנות צמחוניות'
    assert meta['_c99_group_size'] == '20'
    assert meta['_c99_budget_per_person'] == '45.50'
    assert meta['_c99_organisation'] == ''
    assert meta['_c99_source_url'] == 'https://complete99.co.il/request-proposal/'
    assert set(meta) == {
        '_c99_contact_name', '_c99_organisation', '_c99_email', '_c99_phone',
        '_c99_message', '_c99_language', '_c99_interest', '_c99_consent_at',
        '_c99_source_url', '_c99_group_size', '_c99_requested_date',
        '_c99_service_window', '_c99_fulfilment', '_c99_packaging',
        '_c99_budget_per_person', '_c99_preference_summary',
    }


@pytest.mark.parametrize('options', [
    {'insert_failure': True},
    {'write_failure': '_c99_contact_name'},
    {'write_failure': '_c99_preference_summary'},
    {'readback_corruption': '_c99_email'},
])
def test_storage_failure_never_reports_success_and_discards_partial_record(options):
    result = persist(options)
    assert result['result'] == 'rejected:500'
    assert result['redirect'] is None
    assert not result['posts'] and not result['meta']


def test_failed_cleanup_leaves_private_reference_only():
    result = persist({'write_failure': '_c99_group_size', 'delete_failure': True})
    assert result['result'] == 'rejected:500'
    assert result['redirect'] is None
    assert not result['meta']['101']
    assert result['posts']['101']['post_status'] == 'private'
    assert result['posts']['101']['post_title'] == 'C99-STORAGE-FAILED-101'


def test_false_update_result_with_identical_readback_is_not_a_failure():
    result = persist({'unchanged_write_result': True})
    assert result['result'] == 'redirected'
    assert result['fresh_reads'] == 1


@pytest.mark.parametrize('referer,expected', [
    ('https://complete99.co.il/en/request-proposal/?private=value#form',
     'https://complete99.co.il/en/request-proposal/'),
    ('https://outside.example/request/', ''),
])
def test_source_drops_query_and_external_origin(referer, expected):
    result = persist({'referer': referer})
    assert result['meta']['101']['_c99_source_url'] == expected
    assert result['redirect'] == (expected or 'https://complete99.co.il/') + '?c99_sent=1'


def test_rate_limited_request_does_not_create_record():
    result = persist({'rate_count': 5})
    assert result['result'] == 'rejected:429'
    assert not result['posts'] and result['redirect'] is None


def test_honeypot_does_not_create_record_or_success_receipt():
    result = persist(website='bot.invalid')
    assert result['result'] == 'redirected'
    assert not result['posts']
    assert result['redirect'] == 'https://complete99.co.il/request-proposal/'
