@if (count($events) === 0)
    <div class="empty">Nessun evento da sistemare in questo periodo.</div>
@else
    <div class="table-wrap scroll">
        <table>
            <thead>
            <tr>
                <th class="ck"><input type="checkbox" data-all aria-label="Seleziona tutti"></th>
                <th>ID</th>
                <th>Gioco</th>
                <th>Partenza</th>
                <th>Pal./evento interno</th>
                <th>Pal./evento ADM</th>
                <th>Stato</th>
                @if ($extra === 'sent')<th>Card inviata</th>@endif
                @if ($extra === 'result')<th>Fine gara</th><th>Arrivo</th>@endif
            </tr>
            </thead>
            <tbody>
            @foreach ($events as $e)
                <tr>
                    <td class="ck"><input type="checkbox" name="ids[]" value="{{ $e->id }}" aria-label="Seleziona evento {{ $e->id }}"></td>
                    <td class="num"><a href="?evento={{ $e->id }}">{{ $e->id }}</a></td>
                    <td>{{ $channels[$e->channel_id] ?? $e->channel_id }}</td>
                    <td class="num">{{ \Carbon\Carbon::parse($e->time)->format('d/m H:i:s') }}</td>
                    <td class="num">{{ $e->int_pal_id }}/{{ $e->int_event_id }}</td>
                    <td class="num">{{ $e->ext_pal_id ? $e->ext_pal_id . '/' . $e->ext_event_id : '—' }}</td>
                    <td>
                        <span class="badge {{ (int) $e->status === 3 ? 'warn' : '' }}">{{ \App\Libraries\MaintenanceOps::statusLabel($e->status) }}</span>
                    </td>
                    @if ($extra === 'sent')
                        <td class="num">
                            @if ($e->sent_on)
                                {{ \Carbon\Carbon::parse($e->sent_on)->format('d/m H:i') }}
                            @else
                                <span class="badge err">mai inviata</span>
                            @endif
                        </td>
                    @endif
                    @if ($extra === 'result')
                        <td class="num">{{ $e->time_finish ? \Carbon\Carbon::parse($e->time_finish)->format('d/m H:i:s') : '—' }}</td>
                        <td class="num">{{ implode('-', $e->getOpts()['arrivalOrder'] ?? []) }}</td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
