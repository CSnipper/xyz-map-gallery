XYZ Map Gallery — Release v0.1.0

Krótko
-----
Wersja 0.1.0 wprowadza kluczowe usprawnienia po stronie panelu administracyjnego:

- Możliwość wskazania i zapisania punktu środkowego mapy (center picker) w ustawieniach mapy.
- Przeniesienie wszystkich ważnych skryptów administracyjnych do enqueued assets (zamiast inline), co eliminuje problemy z kolejnością ładowania i poprawia utrzymanie.
- Responsywne style panelu administracyjnego: mini-mapa jest w kolumnie po prawej na desktopie i układa się nad formularzem na małych ekranach.
- Pomocnik "Bulk assign" przeniesiony do `assets/js/admin-bulk.js` (select map obok Bulk actions + walidacja).

Szczegóły zmian
---------------
- Dodano: `center_lat`, `center_lng` (kolumny w tabeli `wp_xyz_maps` — tworzone przy instalacji).
- Dodano pliki:
  - `assets/js/admin-center.js` — centralna inicjalizacja mini-mapy (czeka na Leaflet, ustawia marker, aktualizuje ukryte pola).
  - `assets/js/admin-bulk.js` — logika pomocnicza dla Bulk assign.
  - `assets/css/admin-center.css` — style responsywne.
- Zmieniono:
  - `includes/admin/map-settings.php` — usunięto inline JS, dodano klasy układu.
  - `includes/admin/bulk-assign.php` — usunięto inline JS (zastąpione przez `admin-bulk.js`).
  - `includes/admin/assets.php` — dołączono i zlokalizowano skrypty oraz style.

Checklist QA (sugerowane testy przed publikacją)
-------------------------------------------------
1. Admin → Maps → Edit map
   - Otwórz edycję mapy, kliknij mini-mapę aby ustawić punkt środkowy, kliknij "Save Map".
   - Po przeładowaniu edycji współrzędne `center_lat` i `center_lng` powinny być zapisane i marker powinien być widoczny.
2. Frontend
   - Strona, na której osadzono "big map" powinna przy starcie posługiwać się zapisanym środkiem (jeśli jest ustawiony).
3. Admin → Markers
   - Na liście markerów obok Bulk actions powinien być select "Map". Przy próbie Bulk assign bez wybrania mapy powinien pojawić się alert.
4. Console
   - Brak błędów JS powiązanych z Leaflet lub center-pickerem na stronach admin.

Instrukcja wydania (git)
------------------------
```powershell
git checkout -b feat/admin-center-picker
git add .
git commit -m "feat(admin): add persistent center picker, responsive admin UI and move inline admin JS"
git push origin feat/admin-center-picker
# Po review i merge (na branch main/master):
git tag -a v0.1.0 -m "Initial release: admin center picker and admin refactor"
git push origin v0.1.0
```

Uwagi dotyczące migracji
-----------------------
- To pierwsze wydanie wtyczki — instalator tworzy pełną strukturę tabeli `wp_xyz_maps` z kolumnami `center_lat` i `center_lng`. Nie dodajemy ALTER TABLE dla upgrade'ów.

Treść do GitHub Release (kopiuj/wklej)
--------------------------------------
Tytuł: XYZ Map Gallery v0.1.0 — Admin improvements & center picker

Opis:
W tej wersji dodajemy możliwość ustawienia punktu środkowego mapy w panelu administracyjnym oraz gruntowny refactor administracyjnych skryptów.

Najważniejsze zmiany:
- Persistent center picker w Ustawieniach Mapy (admin).
- Przeniesienie inline JS do enqueued assets dla stabilniejszego ładowania.
- Responsywne UI admina i nowy helper Bulk assign.

Zalecenia przed aktualizacją:
- Uruchomić QA zgodnie z checklistą powyżej na środowisku staging.

