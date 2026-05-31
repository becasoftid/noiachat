<select class="noia-select w-full" name="contact_id">@foreach($contacts as $contact)<option value="{{ $contact->id }}">{{ $contact->full_name }} · {{ $contact->primary_phone }}</option>@endforeach</select>
<select class="noia-select mt-3 w-full" name="channel_id">@foreach($channels as $channel)<option value="{{ $channel->id }}">{{ $channel->name }}</option>@endforeach</select>
