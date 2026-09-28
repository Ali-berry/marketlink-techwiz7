@php $audienceOptions = ['everyone' => 'Everyone', 'customers' => 'Customers only', 'farmers' => 'Farmers only']; @endphp

<div class="grid gap-6">
    <div>
        <label class="form-label" for="title">Title</label>
        <input id="title" type="text" name="title" value="{{ old('title', $announcement->title) }}" class="form-input" required>
        <x-input-error :messages="$errors->get('title')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="body">Message</label>
        <textarea id="body" name="body" rows="4" class="form-input" required>{{ old('body', $announcement->body) }}</textarea>
        <x-input-error :messages="$errors->get('body')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="audience">Audience</label>
        <select id="audience" name="audience" class="form-input">
            @foreach ($audienceOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('audience', $announcement->audience) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('audience')" class="mt-1.5" />
    </div>
</div>
