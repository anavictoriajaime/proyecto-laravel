<td>
    @if($cliente->imagen)
        <img src="{{ asset($cliente->imagen) }}" 
             width="50" 
             height="50" 
             style="border-radius:50%; object-fit:cover;">
    @else
        <span style="color:gray;">Sin foto</span>
    @endif
</td>