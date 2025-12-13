<x-mail::message>
Hei {{ $assistant->name }},

Her er oversikt over registrerte arbeidstimer denne måneden.

## {{ \Carbon\Carbon::create($year, $month, 1)->locale('nb')->translatedFormat('F Y') }}

<x-mail::table>
| Dato | Fra-Til | Varighet |
|:-----|:--------|:---------|
@foreach ($shifts as $shift)
| {{ $shift->starts_at->format('d.m.Y') }} | {{ $shift->time_range }} | {{ $shift->formatted_duration }} |
@endforeach
</x-mail::table>

**Total tid:** {{ floor($totalMinutes / 60) }} timer og {{ round($totalMinutes % 60) }} minutter

---

**Estimert lønn før skatt: {{ number_format($estimatedPay, 0, ',', ' ') }} kr**
*(Basert på timesats {{ number_format($hourlyRate, 0, ',', ' ') }} kr)*

Mvh,<br>
{{ config('app.name') }}
</x-mail::message>
