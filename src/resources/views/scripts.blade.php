<script type="text/javascript">

window.messages = {
	'areYouSureMessage' : '{{ __('messages.areYouSureQuestion') }}',
	'areYouSureDestroyMessage' : '{{ __('messages.areYouSureDestroyQuestion') }}'
};

removeRow = function(row)
{
	let table = $(row).parents('table').DataTable();
	table.row(row).remove()
	table.draw();
}

jQuery(document).ready(function($)
{
	//cancellazione elementi
	$('body').on('click', '.button-delete', function()
	{
		var that = this;
		let url = $(this).data('url');

		let destroy = $(this).data('destroy');
		let message = (typeof destroy === 'undefined')? window.messages.areYouSureMessage : window.messages.areYouSureDestroyMessage;

		if(confirm(message))
		{
			$.ajax({
				url : url,
				type : 'DELETE',
				dataType : 'json',
				success : function(response)
				{
					let row = $(that).parents('tr');

					if(response.success === true)
						removeRow(row);

					if(typeof response.message !== 'undefined')
						window.addSuccessNotification(response.message);
	            },
	            error : function(response, message, xhr)
	            {
	                if((response.responseJSON.message !== 'undefined')&&(response.responseJSON.message != ''))
	                    alert(response.responseJSON.message);
	                else
	                    alert('element not deleted');
	            }
	        });
	    }
	});
});
	
</script>