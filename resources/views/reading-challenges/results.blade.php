<x-layout>
    <div class="reading-challenges-page">
        <h1>Izaicinajumu rezultati</h1>
        <p>Atseviska vieta, kur salidzinat rezultatus pec laika un minutes. Vari redzet tikai savus vai visparigi visu lietotaju rezultatus.</p>

        <section class="reading-challenges-list uiverse-container">
            <h2 class="uiverse-heading">Filtri</h2>

            <form method="GET" action="{{ route('reading-challenges.results') }}" class="form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end;">
                <label>
                    Skats
                    <select name="scope" class="input">
                        <option value="mine" @selected($scope === 'mine')>Mani</option>
                        <option value="all" @selected($scope === 'all')>Visi</option>
                    </select>
                </label>

                <label>
                    Periods
                    <select name="period" class="input">
                        <option value="7d" @selected($period === '7d')>Pedejas 7 dienas</option>
                        <option value="30d" @selected($period === '30d')>Pedejas 30 dienas</option>
                        <option value="90d" @selected($period === '90d')>Pedejas 90 dienas</option>
                        <option value="all" @selected($period === 'all')>Viss laiks</option>
                    </select>
                </label>

                <label>
                    Min minutes
                    <input type="number" min="0" name="min_minutes" value="{{ $minMinutes }}" class="input" placeholder="piem. 20">
                </label>

                <button type="submit" class="login-button">Filtrēt</button>
            </form>
        </section>

        <section class="reading-challenges-list uiverse-container" style="margin-top: 16px;">
            <h2 class="uiverse-heading">Kopsavilkums</h2>
            <div class="reading-challenges-cards">
                <article class="reading-challenge-card">
                    <p><strong>Sesijas:</strong> {{ $totalSessions }}</p>
                </article>
                <article class="reading-challenge-card">
                    <p><strong>Kopejas minutes:</strong> {{ $totalMinutes }}</p>
                </article>
                <article class="reading-challenge-card">
                    <p><strong>Kopejas lapas:</strong> {{ $totalPages }}</p>
                </article>
            </div>
        </section>

        <section class="reading-challenges-list uiverse-container" style="margin-top: 16px;">
            <h2 class="uiverse-heading">Rezultatu saraksts</h2>

            @if ($sessions->isEmpty())
                <p>Pec izveletajiem filtriem rezultatu nav.</p>
            @else
                <div class="reading-challenges-cards">
                    @foreach ($sessions as $session)
                        <article class="reading-challenge-card">
                            <p><strong>Lietotajs:</strong> {{ $session->user?->name ?? 'Nezinams lietotajs' }}</p>
                            <p><strong>Datums:</strong> {{ optional($session->created_at)->format('Y-m-d H:i') }}</p>
                            <p><strong>Planots:</strong> {{ $session->planned_minutes }} min</p>
                            <p><strong>Faktiski:</strong> {{ $session->elapsed_seconds ? floor($session->elapsed_seconds / 60) : $session->planned_minutes }} min</p>
                            <p><strong>Izlasits:</strong> {{ $session->pages_read }} lpp</p>
                            @if ($session->notes)
                                <p class="reading-challenge-notes">{{ $session->notes }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>

                <div style="margin-top: 16px;">
                    {{ $sessions->links() }}
                </div>
            @endif
        </section>
    </div>
</x-layout>
