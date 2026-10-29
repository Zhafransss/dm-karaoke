@props(['status'])
<span class="badge badge-dot {{ config("dmk.statuses.{$status}.badge") }}">{{ config("dmk.statuses.{$status}.label", $status) }}</span>
