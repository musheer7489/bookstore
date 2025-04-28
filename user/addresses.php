<?php
require_once '../includes/config.php';

// Redirect unauthorized users
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC");
$stmt->execute([$_SESSION['user_id']]);
$addresses = $stmt->fetchAll();

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_default'])) {
        $address_id = intval($_POST['address_id']);
        
        // Verify address belongs to user
        $stmt = $pdo->prepare("SELECT 1 FROM addresses WHERE address_id = ? AND user_id = ?");
        $stmt->execute([$address_id, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            // Reset all addresses to non-default
            $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            // Set selected address as default
            $stmt = $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE address_id = ?");
            $stmt->execute([$address_id]);
            
            $_SESSION['success_message'] = 'Default address updated successfully';
            header('Location: addresses.php');
            exit;
        }
    } elseif (isset($_POST['delete_address'])) {
        $address_id = intval($_POST['address_id']);
        
        // Verify address belongs to user
        $stmt = $pdo->prepare("SELECT is_default FROM addresses WHERE address_id = ? AND user_id = ?");
        $stmt->execute([$address_id, $_SESSION['user_id']]);
        $address = $stmt->fetch();
        
        if ($address) {
            if ($address['is_default']) {
                $_SESSION['error_message'] = 'Cannot delete default address. Set another address as default first.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM addresses WHERE address_id = ?");
                $stmt->execute([$address_id]);
                
                $_SESSION['success_message'] = 'Address deleted successfully';
            }
            
            header('Location: addresses.php');
            exit;
        }
    }
}

$page_title = "My Addresses";
require_once '../includes/header.php';

// Display success/error messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <?php include 'account_nav.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>My Addresses</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                    <i class="fas fa-plus me-1"></i> Add New Address
                </button>
            </div>
            
            <?php if (count($addresses) > 0): ?>
                <div class="row">
                    <?php foreach ($addresses as $address): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 <?php echo $address['is_default'] ? 'border-primary' : ''; ?>">
                                <div class="card-body">
                                    <?php if ($address['is_default']): ?>
                                        <span class="badge bg-primary mb-2">Default</span>
                                    <?php endif; ?>
                                    
                                    <address class="mb-4">
                                        <?php echo htmlspecialchars($address['street']); ?><br>
                                        <?php echo htmlspecialchars($address['city'] . ', ' . $address['state']); ?><br>
                                        <?php echo htmlspecialchars($address['country'] . ' - ' . $address['postal_code']); ?>
                                    </address>
                                    
                                    <div class="d-flex gap-2">
                                        <?php if (!$address['is_default']): ?>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="address_id" value="<?php echo $address['address_id']; ?>">
                                                <button type="submit" name="set_default" class="btn btn-sm btn-outline-primary">
                                                    Set as Default
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-sm btn-outline-secondary edit-address" 
                                                data-address-id="<?php echo $address['address_id']; ?>"
                                                data-street="<?php echo htmlspecialchars($address['street']); ?>"
                                                data-city="<?php echo htmlspecialchars($address['city']); ?>"
                                                data-state="<?php echo htmlspecialchars($address['state']); ?>"
                                                data-country="<?php echo htmlspecialchars($address['country']); ?>"
                                                data-postal-code="<?php echo htmlspecialchars($address['postal_code']); ?>">
                                            Edit
                                        </button>
                                        
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="address_id" value="<?php echo $address['address_id']; ?>">
                                            <button type="submit" name="delete_address" class="btn btn-sm btn-outline-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this address?');">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                        <h5>No addresses saved</h5>
                        <p class="text-muted">Add your shipping addresses for faster checkout.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                            <i class="fas fa-plus me-1"></i> Add Address
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAddressModalLabel">Add New Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="save_address.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="street" class="form-label">Street Address</label>
                        <input type="text" class="form-control" id="street" name="street" required>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="state" class="form-label">State</label>
                            <input type="text" class="form-control" id="state" name="state" required>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="country" class="form-label">Country</label>
                            <select class="form-select" id="country" name="country" required>
                                <option value="">Select Country</option>
                                <option value="India">India</option>
                                <option value="United States">United States</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="Canada">Canada</option>
                                <option value="Australia">Australia</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1">
                        <label class="form-check-label" for="is_default">
                            Set as default shipping address
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Address Modal -->
<div class="modal fade" id="editAddressModal" tabindex="-1" aria-labelledby="editAddressModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAddressModalLabel">Edit Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="save_address.php">
                <input type="hidden" name="address_id" id="edit_address_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_street" class="form-label">Street Address</label>
                        <input type="text" class="form-control" id="edit_street" name="street" required>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit_city" class="form-label">City</label>
                            <input type="text" class="form-control" id="edit_city" name="city" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_state" class="form-label">State</label>
                            <input type="text" class="form-control" id="edit_state" name="state" required>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit_country" class="form-label">Country</label>
                            <select class="form-select" id="edit_country" name="country" required>
                                <option value="">Select Country</option>
                                <option value="India">India</option>
                                <option value="United States">United States</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="Canada">Canada</option>
                                <option value="Australia">Australia</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_postal_code" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="edit_postal_code" name="postal_code" required>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_is_default" name="is_default" value="1">
                        <label class="form-check-label" for="edit_is_default">
                            Set as default shipping address
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle edit address button clicks
    $('.edit-address').click(function() {
        const addressId = $(this).data('address-id');
        const street = $(this).data('street');
        const city = $(this).data('city');
        const state = $(this).data('state');
        const country = $(this).data('country');
        const postalCode = $(this).data('postal-code');
        
        $('#edit_address_id').val(addressId);
        $('#edit_street').val(street);
        $('#edit_city').val(city);
        $('#edit_state').val(state);
        $('#edit_country').val(country);
        $('#edit_postal_code').val(postalCode);
        
        // Show the modal
        $('#editAddressModal').modal('show');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>