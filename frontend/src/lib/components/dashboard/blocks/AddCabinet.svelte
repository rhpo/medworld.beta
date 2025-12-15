<script lang="ts">
    import Block from "$lib/components/ui/Block.svelte";
    import TopNotification from "$lib/components/TopNotification.svelte";
    import Input from "$lib/components/ui/Input.svelte";
    import type { Cabinet, Location } from "$lib/types/cabinet";
    import { getPermissionsFromUserType } from "$lib/types/permission";
    import type { SuperAdmin } from "$lib/types/users/superadmin";
    import { Plus, Save, MapPin, Clock } from "@lucide/svelte";
    import MapSelector from "./MapSelector.svelte";
    import { validate, validation, type Fillable } from "$lib/validation";

    interface IProps {
        user: SuperAdmin;
        onClose: () => void;
        onAdd: (cabinet: Cabinet) => void;
    }

    let { user, onClose, onAdd }: IProps = $props();
    console.log("AddCabinet: Component mounted");

    let permissions = getPermissionsFromUserType(user.type);
    let isSaving = $state(false);
    let showSuccessNotification = $state(false);
    let notificationMessage = $state("");
    let locationSelected = $state(false);

    let newCabinet: Partial<Cabinet> = $state({
        name: "",
        phone: "",
        createdAt: new Date(),
        doctors: [],
        assistants: [],
        ratings: [],
        accessHandicap: false,
        hasParking: false,
        hasWifi: false,
        acceptsUrgent: false,
        acceptsInsurance: false,
        hasOnlineConsultation: false,
        acceptsEmergency: false,
        location: {
            address: "",
            latitude: 36.737,
            longitude: 3.0588,
        },
        openingHours: {},
    });

    let workingHours: Record<string, { open: string; close: string }> = $state({
        Monday: { open: "09:00", close: "17:00" },
        Tuesday: { open: "09:00", close: "17:00" },
        Wednesday: { open: "09:00", close: "17:00" },
        Thursday: { open: "09:00", close: "17:00" },
        Friday: { open: "09:00", close: "17:00" },
        Saturday: { open: "09:00", close: "13:00" },
        Sunday: { open: "Closed", close: "Closed" },
    });

    function handleLocationChange(location: Location) {
        newCabinet.location = {
            address: location.address || "Selected location",
            latitude: location.latitude,
            longitude: location.longitude,
        };
        locationSelected = true;
        console.log("Location changed:", newCabinet.location);
    }

    async function handleSave() {
        console.log("===== SAVE CLICKED =====");
        console.log("Permissions:", permissions);
        console.log("Has add_cabinet?", permissions.includes("add_cabinet"));
        console.log("Name:", newCabinet.name);
        console.log("Phone:", newCabinet.phone);
        console.log("Location:", newCabinet.location);

        if (!permissions.includes("add_cabinet")) {
            console.error("BLOCKED: No permission");
            alert("You don't have permission");
            return;
        }

        if (!newCabinet.name?.trim() || !newCabinet.phone?.trim()) {
            console.error("BLOCKED: Missing fields");
            alert("Fill name and phone");
            return;
        }

        if (
            !newCabinet.location?.address ||
            newCabinet.location.address === ""
        ) {
            console.error("BLOCKED: No location selected");
            alert("Please select a location on the map");
            return;
        }

        isSaving = true;
        try {
            console.log("Creating cabinet...");

            // console.log('Cabinet created with ID:', id);
            console.log("Adding to store...");

            console.log("✓ Store updated");

            notificationMessage = `Cabinet added!`;
            showSuccessNotification = true;
            console.log("✓ Notification set");

            // onAdd(cabinet);
            console.log("✓ Callback called");

            setTimeout(() => {
                console.log("Closing form...");
                onClose();
            }, 2000);
        } catch (error) {
            console.error("FATAL ERROR:", error);
            alert("Error: " + String(error));
        } finally {
            isSaving = false;
        }
    }
    let data: Fillable = $state({
        name: {
            value: "",
            error: "",
            validator: validation.name,
        },
        phoneNumber: {
            value: "",
            error: "",
            validator: validation.phoneNumber,
        },
    });

    function validateField(fieldName: keyof typeof data) {
        const field = data[fieldName];
        const validationError = field.validator
            ? field.validator(String(field.value ?? ""))
            : "";
        field.error = validationError;
    }

    function add() {
        validateField("name");
        validateField("phoneNumber");

        // Check if any field has error
        const hasErrors = Object.keys(data).some(
            (key) => data[key as keyof typeof data].error !== "",
        );

        if (hasErrors) {
            alert("Please correct the errors in the form.");
            return;
        }

        let error = validate(data);
        if (error) {
            return alert(error);
        }
        window.location.href = "/dashboard";
    }
</script>

<Block Icon={Plus} group="add_cabinet" title="Add New Cabinet">
    {#if showSuccessNotification}
        <TopNotification
            title={notificationMessage}
            color="#10b981"
            bind:hidden={showSuccessNotification}
        />
    {/if}

    <div class="form">
        <Input
            label="Cabinet Name"
            placeholder="Enter cabinet name"
            bind:value={data.name.value}
            bind:error={data.name.error}
            validation={data.name.validator}
            theme="secondary"
            showLabel={true}
            required={true}
        />

        <Input
            label="Phone"
            placeholder="Enter phone number"
            bind:value={data.phoneNumber.value}
            bind:error={data.phoneNumber.error}
            validation={data.phoneNumber.validator}
            theme="secondary"
            showLabel={true}
            required={true}
            type="tel"
        />

        <div class="field">
            <label><MapPin size={18} /> Location *</label>
            <div class="map-container">
                <MapSelector
                    location={newCabinet.location || {
                        address: "",
                        latitude: 36.737,
                        longitude: 3.0588,
                    }}
                    onChange={handleLocationChange}
                />
            </div>
            {#if newCabinet.location?.address && newCabinet.location.address !== ""}
                <div class="location-info">
                    <p>
                        <strong>Selected:</strong>
                        {newCabinet.location.address}
                    </p>
                    <p>
                        <strong>Coordinates:</strong>
                        {newCabinet.location.latitude.toFixed(4)}, {newCabinet.location.longitude.toFixed(
                            4,
                        )}
                    </p>
                </div>
            {/if}
        </div>

        <div class="field">
            <label><Clock size={18} /> Opening Hours</label>
            <div class="hours-grid">
                {#each Object.entries(workingHours) as [day, hours]}
                    <div class="day-hours">
                        <span class="day-name">{day}</span>
                        <div class="time-inputs">
                            <Input
                                type="time"
                                bind:value={hours.open}
                                disabled={hours.open === "Closed"}
                                theme="secondary"
                            />
                            <span>to</span>
                            <Input
                                type="time"
                                bind:value={hours.close}
                                disabled={hours.close === "Closed"}
                                theme="secondary"
                            />
                        </div>
                    </div>
                {/each}
            </div>
        </div>

        <div class="actions">
            <button onclick={onClose}>Cancel</button>
            <button
                onclick={handleSave}
                disabled={isSaving || !locationSelected}
            >
                <Save size={18} />
                <span>{isSaving ? "Adding..." : "Add Cabinet"}</span>
            </button>
        </div>
    </div>
</Block>

<style>
    .form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .field {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .field label {
        font-weight: 500;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .map-container {
        border: 2px solid var(--border-color);
        border-radius: var(--border-radius-md);
        overflow: hidden;
        height: 350px;
    }

    .location-info {
        padding: 0.75rem;
        background: var(--color-primary-alpha);
        border-radius: var(--border-radius-md);
        margin-top: 0.5rem;
    }

    .location-info p {
        margin: 0.25rem 0;
        font-size: 0.9rem;
        color: var(--text-primary);
    }

    .hours-grid {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        padding: 1rem;
        background: var(--background-secondary);
        border-radius: var(--border-radius-md);
    }

    .day-hours {
        display: grid;
        grid-template-columns: 80px 80px auto 80px;
        align-items: center;
        gap: 0.5rem;
    }

    .day-name {
        font-weight: 500;
        font-size: 0.9rem;
    }

    .time-inputs {
        display: contents;
    }

    .time-inputs span {
        font-size: 0.85rem;
        color: var(--text-secondary);
        text-align: center;
    }

    .actions {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color-light);
    }

    button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: var(--border-radius-md);
        font-family: var(--font-secondary);
        font-weight: 500;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all var(--transition-normal);
    }

    button:first-of-type {
        background: var(--color-primary-light);
        color: var(--white);
    }

    button:last-of-type {
        background: var(--color-primary-dark);
        color: var(--white);
        box-shadow: 0 4px 12px rgba(var(--shadow-color-rgb), 0.3);
    }

    button:last-of-type:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    button:last-of-type:hover:not(:disabled) {
        box-shadow: 0 6px 16px rgba(var(--shadow-color-rgb), 0.4);
    }
</style>
