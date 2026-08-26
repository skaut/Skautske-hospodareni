# Acceptance False Failures

## 2026-07-20 - settings/admin CI group

- Command: `make ci-acceptance TEST='--group settings --group admin'`
- Failed test: `acceptance\SettingsCest:userCanDisableAutomaticHelpDisplay`
- Visible failure: `NoSuchElementException` while waiting for `[data-test="settings-user-page"]`
- Artifact: `/home/dorazil/Downloads/_unzip/acceptance.SettingsCest.userCanDisableAutomaticHelpDisplay.ci.fail.html`
- Screenshot: `/tmp/ai-chat-attachment-8691019218327283946.png`
- Artifact root cause: Tracy blue screen with `Skautis\Wsdl\WsdlException: Could not connect to host`
- SkautIS call: `LoginUpdateRefresh` from `Skautis\User::updateLogoutTime()`, called by `App\Model\User\UserService::updateLogoutTime()` during settings presenter startup
- Repeating pattern: transient SkautIS/WSDL pages surface as unrelated element wait failures. This run threw `NoSuchElementException`, while existing global WSDL classification only handled `TimeoutException`.
- Robust fix direction: classify WSDL blue screens from all WebDriver wait failures and route settings section navigation through the shared SkautIS-aware retry helper.
