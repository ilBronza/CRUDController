<div class="crud-client-test-environment" role="alert">
	<div class="crud-client-test-environment__banner">
		@lang('crud::crud.clientTestEnvironmentBanner', [
			'label' => env('CLIENT_TEST_ENV_LABEL') ?: __('crud::crud.clientTestEnvironmentBannerDefault'),
		])
	</div>
</div>

<style>
	.crud-client-test-environment {
		margin: 0 0 1rem;
	}

	.crud-client-test-environment__banner {
		padding: 0.65rem 1rem;
		background: #f39c12;
		color: #fff;
		font-weight: 700;
		text-align: center;
		text-transform: uppercase;
		border-radius: 4px;
	}

	body {
		border-top: 5px solid #d35400;
	}
</style>
