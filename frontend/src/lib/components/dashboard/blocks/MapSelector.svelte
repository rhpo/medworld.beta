<script lang="ts">
    import { onMount } from "svelte";
    import { fade } from "svelte/transition";
    import type { Location } from "$lib/types/cabinet";
    import { MapPin } from "@lucide/svelte";
    import { validate, validation, type Fillable } from "$lib/validation";
    import "leaflet/dist/leaflet.css";
    import Input from "$lib/components/ui/Input.svelte";

    interface IProps {
        location: Location;
        onChange: (location: Location) => void;
    }

    let { location, onChange }: IProps = $props();
    let mapContainer: HTMLDivElement;
    let map: any;
    let marker: any;
    let searchResults = $state<any[]>([]);
    let showResults = $state(false);

    const searchValidator = (query: string) => {
        return query.trim().length >= 2
            ? ""
            : "Search query must be at least 2 characters.";
    };

    let formData: Fillable = $state({
        searchQuery: {
            value: "",
            error: "",
            validator: searchValidator,
        },
    });

    function validateField(fieldName: keyof typeof formData) {
        const field = formData[fieldName];
        const validationError = field.validator
            ? field.validator(String(field.value ?? ""))
            : "";
        field.error = validationError;
    }

    onMount(() => {
        let cleanup: (() => void) | null = null;

        (async () => {
            try {
                // Dynamically import Leaflet on client side
                // @ts-ignore
                const L = (await import("leaflet")).default;

                if (!mapContainer) return;

                // Fix Leaflet default icon issue
                delete (L.Icon.Default.prototype as any)._getIconUrl;
                L.Icon.Default.mergeOptions({
                    iconRetinaUrl:
                        "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png",
                    iconUrl:
                        "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png",
                    shadowUrl:
                        "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png",
                });

                // Initialize map
                map = L.map(mapContainer).setView(
                    [location.latitude || 36.737, location.longitude || 3.0588],
                    13,
                );

                L.tileLayer(
                    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
                    {
                        attribution: "© OpenStreetMap contributors",
                        maxZoom: 19,
                    },
                ).addTo(map);

                // Add marker
                marker = L.marker([
                    location.latitude || 36.737,
                    location.longitude || 3.0588,
                ])
                    .addTo(map)
                    .bindPopup("Cabinet Location");

                // Click to place marker
                map.on("click", (e: any) => {
                    const { lat, lng } = e.latlng;
                    marker.setLatLng([lat, lng]);
                    onChange({
                        ...location,
                        latitude: lat,
                        longitude: lng,
                    });
                });
            } catch (error) {
                console.error("Map initialization error:", error);
            }
        })();

        cleanup = () => {
            try {
                if (map) map.remove();
            } catch (e) {
                console.error("Cleanup error:", e);
            }
        };

        return cleanup;
    });

    async function searchLocation() {
        validateField("searchQuery");

        if (formData.searchQuery.error) {
            return;
        }

        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(
                    formData.searchQuery.value,
                )}&countrycodes=dz`,
            );
            const results = await response.json();
            searchResults = results.slice(0, 5);
            showResults = true;
        } catch (error) {
            console.error("Search error:", error);
            formData.searchQuery.error = "Failed to search location";
        }
    }

    function selectSearchResult(result: any) {
        const lat = parseFloat(result.lat);
        const lng = parseFloat(result.lon);

        if (map && marker) {
            map.setView([lat, lng], 15);
            marker.setLatLng([lat, lng]);
        }

        onChange({
            address: result.display_name,
            latitude: lat,
            longitude: lng,
        });

        formData.searchQuery.value = "";
        formData.searchQuery.error = "";
        searchResults = [];
        showResults = false;
    }
</script>

<div class="map-container">
    <div class="search-box">
        <div class="search-input-group">
            <div class="input-wrapper">
                <Input
                    placeholder="Search location..."
                    bind:value={formData.searchQuery.value}
                    bind:error={formData.searchQuery.error}
                    validation={formData.searchQuery.validator}
                    onInput={searchLocation}
                    class="search-input"
                    aria-label="Search for cabinet location"
                    theme="nothing"
                />
            </div>
            <MapPin
                size={20}
                class="search-icon"
                style="position: absolute; left: 0.75rem; color: var(--color-primary); pointer-events: none;"
            />
        </div>

        {#if showResults && searchResults.length > 0}
            <div class="search-results" transition:fade>
                {#each searchResults as result}
                    <button
                        class="result-item"
                        onclick={() => selectSearchResult(result)}
                        type="button"
                    >
                        <MapPin size={16} />
                        <span>{result.display_name}</span>
                    </button>
                {/each}
            </div>
        {/if}
    </div>

    <div class="map-info">
        <p><strong>Latitude:</strong> {location.latitude.toFixed(6)}</p>
        <p><strong>Longitude:</strong> {location.longitude.toFixed(6)}</p>
        <p><strong>Address:</strong> {location.address || "Not set"}</p>
        <p class="hint">💡 Click on the map to place marker or use search</p>
    </div>

    <div bind:this={mapContainer} class="map"></div>
</div>

<style>
    .map-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        border-radius: 0.75rem;
        overflow: hidden;
        background: var(--background-primary);
    }

    .search-box {
        position: relative;
        padding: 1rem;
        border-bottom: 1px solid var(--color-primary-alpha);
    }

    .search-input-group {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-wrapper {
        width: 100%;
    }

    .input-wrapper :global(main) {
        margin-bottom: 0 !important;
    }

    :global(.search-input) {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.5rem;
        border: 1px solid var(--color-primary-alpha);
        border-radius: 0.5rem;
        background-color: var(--background-secondary);
        color: var(--text-color);
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }

    :global(.search-input:focus) {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 2px var(--color-primary-alpha);
    }

    .search-results {
        position: absolute;
        top: 100%;
        left: 1rem;
        right: 1rem;
        background: var(--background-secondary);
        border: 1px solid var(--color-primary-alpha);
        border-top: none;
        border-radius: 0 0 0.5rem 0.5rem;
        max-height: 300px;
        overflow-y: auto;
        z-index: 10;
        margin-top: -1px;
    }

    .result-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
        padding: 0.75rem 1rem;
        background: none;
        border: none;
        border-bottom: 1px solid var(--color-primary-alpha);
        color: var(--text-color);
        cursor: pointer;
        text-align: left;
        font-size: 0.875rem;
        transition: background-color 0.2s ease;
    }

    .result-item:last-child {
        border-bottom: none;
    }

    .result-item:hover {
        background-color: var(--color-primary-alpha);
    }

    .map-info {
        padding: 1rem;
        background: var(--background-secondary);
        border-radius: 0.5rem;
        font-size: 0.875rem;
        color: var(--text-color);
    }

    .map-info p {
        margin: 0.25rem 0;
    }

    .hint {
        margin-top: 0.75rem;
        color: var(--text-color-secondary);
        font-style: italic;
    }

    .map {
        height: 400px;
        background: var(--background-secondary);
        border-radius: 0.5rem;
        margin: 0 1rem 1rem;
    }

    @media (max-width: 768px) {
        .map {
            height: 300px;
        }

        .search-results {
            max-height: 200px;
        }
    }
</style>
